<?php
require_once __dir__ . "/../core/Database.php";
require_once __dir__ . "/../core/HTTPRequest.php";
require_once __dir__ . "/../core/Logger.php";
require_once __dir__ . "/../common/functions.php";

/**
 * This function takes database credentials and github credentials and fetches commit for all the branches of the repo and inserts in the databse.
 * githubAuthToken is option, if the token is not present for the user or you want to update the previous token then you can provide it. 
 * 
 */
function fetchRepoWithCommits($dbHost, $dbUser, $dbPassword, $dbName, $githubUsername, $repoName, $pageSize = 100, $FETCH_TOTAL_COMMITS_CAP = 1000 , $githubAuthToken = "")
{
    $response = array();
    $response['status'] = false;
    $processedBranches = array();

    $githubUsername = trim($githubUsername);
    $repoName = trim($repoName);
    $githubAuthToken = trim($githubAuthToken);

    $dbHost = trim($dbHost);
    $dbUser = trim($dbUser);
    $dbPassword = $dbPassword;
    $dbName = trim($dbName);

    if (empty($githubUsername)) {
        $response['message'] = "Please provide githubUsername";
        return formatResponse($response);
    }
    if (empty($repoName)) {
        $response['message'] = "Please provide repoName";
        return formatResponse($response);
    }
    if (empty($dbHost)) {
        $response['message'] = "Please provide dbHost";
        return formatResponse($response);
    }
    if (empty($dbUser)) {
        $response['message'] = "Please provide dbUser";
        return formatResponse($response);
    }
    if (!isset($dbPassword)) {
        $response['message'] = "Please provide dbPassword";
        return formatResponse($response);
    }
    if (empty($dbName)) {
        $response['message'] = "Please provide dbName";
        return formatResponse($response);
    }
    //Initial logger and database classes
    initiate($dbHost, $dbUser, $dbPassword, $dbName);

    $userInfo = getUserByGithubUsername($githubUsername);
    if (!isset($userInfo['id'])) { //Need to create new user with the givn githubUsername
        insertUser($githubUsername);
        $userInfo = getUserByGithubUsername($githubUsername);
    }
    $userId = $userInfo['id'];

    if (empty($userInfo['githubAuthToken'])) { //We dont have an existing githubAuthToken for the user
        if (empty($githubAuthToken)) { //User has not provided githubAuthToken in this request
            $response['message'] = "Please provide githubAuthToken";
            return formatResponse($response);
        }
    }

    if (!empty($githubAuthToken)) { //User has given a githubAuthToken
        updateGithubAuthToken($userInfo['id'], $githubAuthToken);
    } else { //User has not given githubAuthToken, use the one which is already there in database
        $githubAuthToken = $userInfo['githubAuthToken'];
    }
    $repoInfo = getRepoByuserIdRepoName($userId, $repoName);
    if (!isset($repoInfo['id'])) { //Need to insert this repository in database for the user
        insertRepo($userId, $repoName);
        $repoInfo = getRepoByuserIdRepoName($userId, $repoName);
    }
    $repoName = $repoInfo['name'];
    $repoId = $repoInfo['id'];


    $branches = fetchAllBranchesForRepo($repoId, $githubUsername, $repoName, $githubAuthToken);
    if (isset($branches[0]) && isset($branches[0]['commit'])) {
        for ($i = 0; $i < count($branches); $i++) {
            $branch = $branches[$i];
            if (!isset($branch['commit'])) {
                continue;
            }
            $branchCommitsCount = 0;
            if (isset($branch['needCommitFetch']) && $branch['needCommitFetch'] === true) {
                Logger::info("===================================================================================");
                Logger::info("Fetching commits for branch no (" . $i . ") [" . $branch['name'] . "]");

                $taskId = createTask($branch['branchIdDB'], $branch['commit']['sha']);
                $task = getTaskByTaskId($taskId, 0);
                if (count($task) > 0 && isset($task['id'])) {
                    $branchCommitsCount = fetAllCommitsForBranch($githubUsername, $repoName, $githubAuthToken, $task['branchId'], $task['commitSHA'], $task['id'], $pageSize, $FETCH_TOTAL_COMMITS_CAP);
                } else {
                    Logger::error("Could not create task to fetch commits");
                }
                //$branch['commitsCount'] = $branchCommitsCount;
            }
            $processedBranches[] = array(
                'id' => $branch['branchIdDB'],
                'name' => $branch['name'],
                'commitsFetched' => $branchCommitsCount,
            );
        }
        //branches['commitsFetched'] = $branchCommitsCount;
        $response['status'] = true;
        $response['branches'] = $processedBranches;
    } else {
        $response['message'] = "No branches found for the given user and repo";
    }
    return formatResponse($response);
}
/**
 * Function to format output before returning
 */
function formatResponse($response)
{
    return json_encode($response);
}

function initiate($dbHost, $dbUser, $dbPassword, $dbName)
{
    Database::$host = $dbHost;
    Database::$user = $dbUser;
    Database::$pass = $dbPassword;
    Database::$dbName = $dbName;
    Logger::$write_log = true;
    Logger::$print_log = false; //Whether to print log entries to screen as they are added.
    Logger::$log_file_name = 'pull-commits';
    Logger::$log_file_extension = 'log';
    Logger::$log_level = 'debug';
}

/**
 * Function to fetch all the branches of a repository and save it in database
 */
function fetchAllBranchesForRepo($repoId, $githubUsername, $repoName, $githubAuthToken)
{

    $url = GITHUB_API_HOST . "/repos/" . $githubUsername . "/" . $repoName . "/branches";
    $header = "Authorization: token " . $githubAuthToken;

    $response = callAPI("GET", $url, array(), $header);
    $branches = json_decode($response, true);

    if (count($branches) > 0 && isset($branches[0]['name'])) {
        Logger::info("Got " . count($branches) . " repos");
        for ($i = 0; $i < count($branches); $i++) {
            $branch = $branches[$i];
            Logger::info("===================================================================================");
            Logger::info("Processing branch no (" . $i . ") [" . $branch['name'] . "]");
            /************************* START OF Get commit info ****************************/
            $url = GITHUB_API_HOST . "/repos/" . $githubUsername . "/" . $repoName . "/commits/" . $branch['commit']['sha'];
            Logger::info("Fetching details for latest commit of branch... [" . $branch['name'] . "]");
            $response = callAPI("GET", $url, array(), $header);
            $commitDetail = json_decode($response, true);
            $commitDateTime = $commitDetail['commit']['committer']['date'];
            /*********************** END OF Get commit info ****************************/
            try {
                Logger::info("Inserting record in database for branch... [" . $branch['name'] . "]");
                $db = Database::openConnection();
                // inserting data into create table using prepare statement to prevent from sql injections
                $sqlString = "INSERT INTO branch (`name`,`repoId`,`lastCommitSHA`,`lastCommitDatetime`) VALUES ( :name, :repoId, :lastCommitSHA, :lastCommitDatetime)";
                $stm = $db->prepare($sqlString);
                // inserting a record
                $stm->execute(array(':name' => $branch['name'], ':repoId' => $repoId, ':lastCommitSHA' => $branch['commit']['sha'], ':lastCommitDatetime' => $commitDateTime));
                $branches[$i]['branchIdDB'] = $db->lastInsertId();
                $branches[$i]['needCommitFetch'] = true;
                Database::closeConnection($db);
            } catch (PDOException $exception) {
                if ($exception->getCode() === "23000") { //This repo is already there in the database
                    Logger::warning("Branch [" . $branch['name'] . "] already exists in database");
                    $branchDBInfo = getBranchFromDBByRepoIdAndBranchName($repoId, $branch['name']);
                    $branches[$i]['branchIdDB'] = $branchDBInfo['id'];
                    if ($branchDBInfo['lastCommitSHA'] !== $branch['commit']['sha']) { //Check if we have got new commits in this repo
                        Logger::info("Updating lastCommitSHA for branchId [" . $branchDBInfo['id'] . "] name [" . $branch['name'] . "]");
                        updateBranch($branchDBInfo['id'], $branch['commit']['sha'], $commitDateTime);
                        $branches[$i]['needCommitFetch'] = true;
                    } else {
                        $branches[$i]['needCommitFetch'] = false;
                    }
                }
            }
        }
        Logger::info("Fetched all branches successfully for repoId (" . $repoId . ")");
    } else {
        Logger::error("Did not get any branches for repoId (" . $repoId . ") repoName (" . $repoName . ")");
        Logger::error("Github response (" . json_encode($branches) . ")");
    }
    return $branches;
}

/**
 * Function to fetch all the commits of a branch and save it in database
 */
function fetAllCommitsForBranch($githubUsername, $repoName, $githubAuthToken, $branchId, $latestSHA, $fetchProcessId, $pageSize, $FETCH_TOTAL_COMMITS_CAP)
{
    $pageNumber = 1;

    if ($fetchProcessId > 0) {
        lockTask($fetchProcessId, "STARTED");
    }
    $count = 0;
    $todo = true;
    while ($todo) {
        Logger::info("===================================================================================");
        Logger::info("Fetching data for page no (" . $pageNumber . ")");
        $url = GITHUB_API_HOST . "/repos/" . $githubUsername . "/" . $repoName . "/commits?per_page=" . $pageSize . "&sha=" . $latestSHA;
        $header = "Authorization: token " . $githubAuthToken;

        $response = callAPI("GET", $url, array(), $header);
        $commits = json_decode($response, true);
        $lastSHA = -1;
        if (count($commits) > 0 && isset($commits[0]['sha'])) {
            for ($i = 0; $i < count($commits); $i++) {
                $commit = $commits[$i];
                try {
                    Logger::info("Inserting commits in database for branchId... [" . $branchId . "]");
                    $db = Database::openConnection();
                    // inserting data into create table using prepare statement to prevent from sql injections
                    $sqlString = "INSERT INTO `commit` (`branchId`,`commitSHA`,`authorName`,`authorEmail`,`committerName`,`committerEmail`,`commitDatetime`,`commitMessage`) VALUES ( :branchId, :commitSHA, :authorName, :authorEmail, :committerName, :committerEmail, :commitDatetime, :commitMessage)";
                    $stm = $db->prepare($sqlString);
                    // inserting a record
                    $stm->execute(array(
                        ':branchId' => $branchId, ':commitSHA' => $commit['sha'], ':authorName' => $commit['commit']['author']['name'], ':authorEmail' => $commit['commit']['author']['email'], ':committerName' => $commit['commit']['committer']['name'], ':committerEmail' => $commit['commit']['committer']['email'], ':commitDatetime' => $commit['commit']['committer']['date'], ':commitMessage' => $commit['commit']['message']
                    ));
                    $commit[$i]['commitIdDB'] = $db->lastInsertId();
                    $db = Database::closeConnection($db);
                } catch (PDOException $exception) {
                    if ($exception->getCode() === "23000" && $i !== 0) {
                        Logger::warning("Commit [" . $i . "] already exists in database");
                        $commitDBInfo = getCommitFromDBByCommitSHA($commit['sha']);
                        $commit[$i]['commitIdDB'] = $commitDBInfo['id'];
                    }
                }
                $count++;
                Logger::warning("Count:::::::::::" . $count);
                if ($count > $FETCH_TOTAL_COMMITS_CAP) { //We have fetched max limit commits
                    $todo = false;
                    break;
                }
                $lastSHA = $commit['sha'];
            }
            if ($lastSHA === $latestSHA || $todo === false) { //We have fetched all the commits of the repository or reached the cap
                Logger::info("Fetched all commits for branchId [" . $branchId . "]");
                if ($fetchProcessId > 0) {
                    markDoneTask($fetchProcessId, "Done");
                }
                break;
            } else {
                $latestSHA = $lastSHA;
            }
        } else {
            Logger::error("Did not get any more commits for page(" . $pageNumber . ") branchId [" . $branchId . "]");
            if ($fetchProcessId > 0) {
                updateTask($fetchProcessId, $lastSHA, "FAILED");
            }
            break;
        }
        $pageNumber++;
        if ($fetchProcessId > 0) {
            updateTask($fetchProcessId, $lastSHA, "IN-PROGRESS");
        }
    }
    Logger::info("Total commits processed " . $count);

    return $count;
}
