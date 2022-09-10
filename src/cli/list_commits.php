<?php
require_once __dir__ . "/../core/Database.php";
require_once __dir__ . "/../core/HTTPRequest.php";
require_once __dir__ . "/../core/Logger.php";
require_once __dir__ . "/../common/functions.php";

Logger::$write_log = true;
Logger::$log_file_name = 'list-commits';
Logger::$log_file_extension = 'log';
Logger::$log_level = 'debug';

if(count($argv)===5){
    $githubUsername = trim($argv[1]);
    $userInfo = getUserByGithubUsername($githubUsername);
    if(!isset($userInfo['id'])){
        Logger::warning("githubUsername ".$githubUsername. "is not present in database."); 
        die;
    }
    $repoName = trim($argv[2]);
    $reoInfo = getRepoByuserIdRepoName($userInfo['id'],$repoName);
    if(!isset($reoInfo['id'])){
        Logger::warning("Repo ".$repoName. " is not present in database."); 
        die;
    }
    
    $startTime = trim($argv[3])." 00:00:00";
    $endTime = trim($argv[4]). " 23:59:59";

    $commits = getCommitsForRepoByDateRange($reoInfo['id'],$startTime,$endTime);
    if(isset($commits[0]['id'])){
        echo "\n";
        $i = 0;
        for(;$i<count($commits);$i++){
            $commit = $commits[$i];
            print_r(json_encode($commit));
            //echo $commit['repoName'],$commit['branchName'],$commit['committerName'],$commit['committerEmail'],$commit['commitDatetime'],$commit['commitMessage']; 
            echo "\n";
        }
        echo "Total commits: ".$i."";
    }else{
        Logger::info("No commits found for the given inputs"); 
    }
}else{
    Logger::warning("Please provide username repo name, start date(YYYY-MM-DD) and end date(YYYY-MM-DD)");
    Logger::warning("Example: php src/cli/list_commits.php shelly-cloud testrepo 2019-08-14 2022-08-30");
}
