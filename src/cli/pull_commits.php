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
if(count($argv)===2){//No input parameters. Script will update all the repositories
    $githubUsername = $argv[1];
}else if(count($argv)===3){//For input parameters: githubUsername, repo
    $githubUsername = $argv[1];
    $userInfo = getUserByGithubUsername($githubUsername);
    if(!isset($userInfo['id'])){//Need to create new user with the givn githubUsername
        insertUser($githubUsername);
        $userInfo = getUserByGithubUsername($githubUsername);
    }
    if(empty($userInfo['githubAuthToken'])){
        $githubAuthToken = prompt_silent("Enter Github access token:");
	var_dump("$githubAuthToken");
        $githubAuthToken = trim($githubAuthToken);
        while(empty($githubAuthToken) || $githubAuthToken===""){
            $githubAuthToken = prompt_silent("Enter Github access token:");
            $githubAuthToken = trim($githubAuthToken);
        }
        updateGithubAuthToken($userInfo['id'],$githubAuthToken);
	echo "cominghere";
	echo $githubAuthToken;
    }else{
        $newToekn = prompt_silent("We already have a Github access token for the user. Press enter to continue with the same token or enter a new token if you want to update the existing token:");
        if(trim($newToekn)!==""){
            $githubAuthToken = $newToekn;
            updateGithubAuthToken($userInfo['id'],$githubAuthToken);
        }else{
            $githubAuthToken = $userInfo['githubAuthToken'];
        }
    }
    $githubUsername = $userInfo['githubUsername'];
    $userId = $userInfo['id'];

    $repoName = $argv[2];
    $repoInfo = getRepoByuserIdRepoName($userId,$repoName);
    if(!isset($repoInfo['id'])){//Need to create new user with the givn githubUsername
        insertRepo($userId,$repoName);
        $repoInfo = getRepoByuserIdRepoName($userId,$repoName);
    }
    $repoName = $repoInfo['name'];
    $repoId = $repoInfo['id'];
}

$branches = fetchAllBranchesForRepo($repoId, $githubUsername, $repoName, $githubAuthToken);
if(isset($branches[0]) && isset($branches[0]['commit'])){
    for ($i = 0; $i < count($branches); $i++) {
        $branch = $branches[$i];
        if(!isset($branch['commit'])){
            continue;
        }
        if (isset($branch['needCommitFetch']) && $branch['needCommitFetch'] === true) {
            Logger::info("===================================================================================");
            Logger::info("Fetching commits for branch no (" . $i . ") [" . $branch['name'] . "]");
            createTask($branch['branchIdDB'], $branch['commit']['sha']);
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
            fetAllCommitsForBranch($githubUsername, $repoName, $githubAuthToken, $task['branchId'], $task['commitSHA'],$task['id']);
        }
    }
}
function fetchAllBranchesForRepo($repoId, $githubUsername, $repoName, $githubAuthToken)
{
    $database = new Database();
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
        Logger::error("Did not get any branches for repoId (" . $repoId . ") repoName (" . $repoName. ")");
        Logger::error("Github response (" . json_encode($branches) . ")");
    }
    return $branches;
}



function fetAllCommitsForBranch($githubUsername, $repoName, $githubAuthToken, $branchId, $latestSHA, $fetchProcessId = 0, $pageSize = PAGE_SIZE)
{
    $pageNumber = 1;
    $database = new Database();
    if ($fetchProcessId > 0) {
        lockTask($fetchProcessId, "STARTED");
    }
    $count=0;
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
                    if ($exception->getCode() === "23000" && $i!==0) {
                        Logger::warning("Commit [" . $i . "] already exists in database");
                        $commitDBInfo = getCommitFromDBByCommitSHA($commit['sha']);
                        $commit[$i]['commitIdDB'] = $commitDBInfo['id'];
                    }
                }
                $count++;
                Logger::warning("Count:::::::::::".$count);
                if($count>FETCH_TOTAL_COMMITS_CAP){//We have fetched max limit commits
                    $todo = false;
                    break;
                }
                $lastSHA = $commit['sha'];
            }
            if ($lastSHA === $latestSHA || $todo === false) {//We have fetched all the commits of the repository or reached the cap
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
    Logger::info("Total commits processed ".$count);
    return true;
}
