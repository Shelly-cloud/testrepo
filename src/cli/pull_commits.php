<?php
require_once __dir__ . "/../core/Database.php";
require_once __dir__ . "/../core/HTTPRequest.php";
require_once __dir__ . "/../core/Logger.php";
require_once __dir__ . "/../common/functions.php";



Logger::$write_log = true;
//Logger::$log_dir = 'logs';
Logger::$log_file_name = 'pull-commits';
Logger::$log_file_extension = 'log';
Logger::$log_level = 'debug';
//Todo: These values will come from database
$userId = 1;
$repoId = 1;
$githubUername = '';//Github username
$repoName = '';//Repo name
$githubUernameAuthToken = '';//Github auth token of the user with the required access
/////////////////////////
$branches = fetchAllBranchesForRepo($repoId, $githubUername, $repoName, $githubUernameAuthToken);

for ($i = 0; $i < count($branches); $i++) {
    $branch = $branches[$i];
    if (isset($branch['needCommitFetch']) && $branch['needCommitFetch'] === true) {
        Logger::info("===================================================================================");
        Logger::info("Fetching commits for branch no (" . $i . ") [" . $branch['name'] . "]");
        createTask($branch['branchIdDB'], $branch['commit']['sha']);
        //fetAllCommitsForBranch($githubUername, $repoName, $githubUernameAuthToken, $branch['branchIdDB'], $branch['commit']['sha']);
    }
}
//This loop keeps running to pick pending process and execute them
$taskCount = 0;
$todo = true;
while ($todo) {
    $taskCount++;
    $task = getTask(0);
    if(count($task)===0){
        Logger::info("No pending commits to be fetched :)");
        $todo=false;
        break;
    }
    if (count($task) > 0 && isset($task['id'])) {
        fetAllCommitsForBranch($githubUername, $repoName, $githubUernameAuthToken, $task['branchId'], $task['commitSHA'],$task['id']);
    }
}
function fetchAllBranchesForRepo($repoId, $githubUername, $repoName, $githubUernameAuthToken)
{
    $database = new Database();
    $url = GITHUB_API_HOST . "/repos/" . $githubUername . "/" . $repoName . "/branches";
    $header = "Authorization: token " . $githubUernameAuthToken;

    $response = callAPI("GET", $url, array(), $header);
    $branches = json_decode($response, true);

    if (count($branches) > 0 && isset($branches[0]['name'])) {
        Logger::info("Got " . count($branches) . " repos");
        for ($i = 0; $i < count($branches); $i++) {
            $branch = $branches[$i];
            Logger::info("===================================================================================");
            Logger::info("Processing branch no (" . $i . ") [" . $branch['name'] . "]");
            /************************* START OF Get commit info ****************************/
            $url = GITHUB_API_HOST . "/repos/" . $githubUername . "/" . $repoName . "/commits/" . $branch['commit']['sha'];
            Logger::info("Fetching details for latest commit of branch... [" . $branch['name'] . "]");
            $response = callAPI("GET", $url, array(), $header);
            $commitDetail = json_decode($response, true);
            $commitDateTime = $commitDetail['commit']['committer']['date'];
            /*********************** END OF Get commit info ****************************/
            try {
                Logger::info("Inserting record in database for branch... [" . $branch['name'] . "]");
                $db = $database->openConnection();
                // inserting data into create table using prepare statement to prevent from sql injections
                $sqlString = "INSERT INTO branch (`name`,`repoId`,`lastCommitSHA`,`lastCommitDatetime`) VALUES ( :name, :repoId, :lastCommitSHA, :lastCommitDatetime)";
                $stm = $db->prepare($sqlString);
                // inserting a record
                $stm->execute(array(':name' => $branch['name'], ':repoId' => $repoId, ':lastCommitSHA' => $branch['commit']['sha'], ':lastCommitDatetime' => $commitDateTime));
                $branches[$i]['branchIdDB'] = $db->lastInsertId();
                $branches[$i]['needCommitFetch'] = true;
                $database->closeConnection();
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
        Logger::error("Did not get any branches for repoId (" . $repoId . ")");
    }
    return $branches;
}



function fetAllCommitsForBranch($githubUername, $repoName, $githubUernameAuthToken, $branchId, $latestSHA, $fetchProcessId = 0, $pageSize = 10)
{
    $pageNumber = 1;
    $database = new Database();
    if ($fetchProcessId > 0) {
        lockTask($fetchProcessId, "STARTED");
    }
    while (true) {
        Logger::info("===================================================================================");
        Logger::info("Fetching data for page no (" . $pageNumber . ")");
        $url = GITHUB_API_HOST . "/repos/" . $githubUername . "/" . $repoName . "/commits?per_page=" . $pageSize . "&sha=" . $latestSHA;
        $header = "Authorization: token " . $githubUernameAuthToken;

        $response = callAPI("GET", $url, array(), $header);
        $commits = json_decode($response, true);
        $lastSHA = -1;
        if (count($commits) > 0 && isset($commits[0]['sha'])) {
            for ($i = 0; $i < count($commits); $i++) {
                $commit = $commits[$i];
                try {
                    Logger::info("Inserting commits in database for branchId... [" . $branchId . "]");
                    $db = $database->openConnection();
                    // inserting data into create table using prepare statement to prevent from sql injections
                    $sqlString = "INSERT INTO `commit` (`branchId`,`commitSHA`,`authorName`,`authorEmail`,`committerName`,`committerEmail`,`commitDatetime`,`commitMessage`) VALUES ( :branchId, :commitSHA, :authorName, :authorEmail, :committerName, :committerEmail, :commitDatetime, :commitMessage)";
                    $stm = $db->prepare($sqlString);
                    // inserting a record
                    $stm->execute(array(
                        ':branchId' => $branchId, ':commitSHA' => $commit['sha'], ':authorName' => $commit['commit']['author']['name'], ':authorEmail' => $commit['commit']['author']['email'], ':committerName' => $commit['commit']['committer']['name'], ':committerEmail' => $commit['commit']['committer']['email'], ':commitDatetime' => $commit['commit']['committer']['date'], ':commitMessage' => $commit['commit']['message']
                    ));
                    $commit[$i]['commitIdDB'] = $db->lastInsertId();
                    $db = $database->closeConnection();
                } catch (PDOException $exception) {
                    if ($exception->getCode() === "23000") {
                        Logger::warning("Commit [" . $i . "] already exists in database");
                        $commitDBInfo = getCommitFromDBByCommitSHA($commit['sha']);
                        $commit[$i]['commitIdDB'] = $commitDBInfo['id'];
                    }
                }
                $lastSHA = $commit['sha'];
            }
            if ($lastSHA === $latestSHA) {
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
    return true;
}
