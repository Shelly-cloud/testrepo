<?php
$data = json_decode(file_get_contents('php://input'), true);
//print_r($data);

require_once __dir__ . "/../core/Database.php";
require_once __dir__ . "/../core/HTTPRequest.php";
require_once __dir__ . "/../core/Logger.php";
require_once __dir__ . "/../common/functions.php";

Logger::$print_log = false;//For console
Logger::$write_log = true;//For file logs
Logger::$log_file_name = 'list-commits';
Logger::$log_file_extension = 'log';
Logger::$log_level = 'debug';

$githubUsername = !empty($data['githubUsername']) ? trim($data['githubUsername']) : "";
$repoName = !empty($data['repoName']) ? trim($data['repoName']) : "";
$startTime = !empty($data['startDate']) ? trim($data['startDate']) : "";
$endTime = !empty($data['endDate']) ? trim($data['endDate']) : "";//end
$return=array();

header('Content-type: text/javascript');

$return['status']=false;
if(empty($githubUsername)){
   $return['message']="Please enter githubUsername";
}else if(empty($repoName)){
   $return['message']="Please enter repoName";
}else if(empty($startTime)){
   $return['message']="Please enter startTime in formate YYYY-MM-DD";
}else if(empty($endTime)){
   $return['message']="Please enter endTime in formate YYYY-MM-DD";
}
else{
    $startTime = $startTime." 00:00:00";
    $endTime = $endTime." 23:59:59";
    $userInfo = getUserByGithubUsername($githubUsername);
    if(!isset($userInfo['id'])){
        $msg = "githubUsername ".$githubUsername. " is not present in database.";
        Logger::warning($msg); 
        $return['message']=$msg;
        echo json_encode($return);
        die;
    }
    $reoInfo = getRepoByuserIdRepoName($userInfo['id'],$repoName);
    if(!isset($reoInfo['id'])){
        $msg = "Repo ".$repoName. " is not present in database.";
        Logger::warning($msg); 
        $return['message']=$msg;
        echo json_encode($return);
        die;
    }

    $commits = getCommitsForRepoByDateRange($reoInfo['id'],$startTime,$endTime);
    if(isset($commits[0]['id'])){
        $i = 0;
        $commitsArr=array();
        for(;$i<count($commits);$i++){
            $commitsArr[] = $commits[$i];
            //print_r(json_encode($commit));
            //echo $commit['repoName'],$commit['branchName'],$commit['committerName'],$commit['committerEmail'],$commit['commitDatetime'],$commit['commitMessage']; 
            //echo "\n";
        }
        $return['status']=true;
        $return['data']=$commitsArr;
        echo json_encode($return);
        die;
        //echo "Total commits: ".$i."";
    }else{
        $msg = "No commits found for the given inputs";
        Logger::info($msg); 
        $return['message']=$msg;
        echo json_encode($return);
        die;
    }
}
echo json_encode($return);
