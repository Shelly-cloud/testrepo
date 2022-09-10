<?php
require_once __dir__ . "/../core/Database.php";
require_once __dir__ . "/../core/Logger.php";
require_once __dir__ . "/encryption.php";


function getUserByGithubUsername($githubUsername)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM user WHERE githubUsername=:githubUsername AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':githubUsername' => $githubUsername));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
            if(!empty($result['githubAuthToken'])){
                $result['githubAuthToken'] = decrypt($result['githubAuthToken'],ENC_KEY,ENC_IV);
            }
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function insertUser($githubUsername)
{   
    $result = false;
    try {
        
        $db = Database::openConnection();
        $sqlString = "INSERT INTO user (`githubUsername`) VALUES ( :githubUsername)";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':githubUsername' => $githubUsername));
        $result = $db->lastInsertId();
        Database::closeConnection($db);
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return $result;
}

function updateGithubAuthToken($id,$githubAuthToken)
{   
    $githubAuthToken = encrypt($githubAuthToken,ENC_KEY,ENC_IV);
    try {
        
        $db = Database::openConnection();
        $sqlString = "UPDATE user SET githubAuthToken=:githubAuthToken WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':githubAuthToken'=>$githubAuthToken));
        Database::closeConnection($db);
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}

function getRepoByuserIdRepoName($userId,$repoName)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM repo WHERE userId=:userId AND name=:repoName AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':userId' => $userId, ':repoName' => $repoName));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function getReposByRepoName($repoName)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM repo WHERE name=:repoName AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':repoName' => $repoName));
        $result = [];
        while ($row = $stm->fetch()) {
            $result[] = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function insertRepo($userId,$repoName)
{   
    $result = false;
    try {
        
        $db = Database::openConnection();
        $sqlString = "INSERT INTO repo (`userId`,`name`) VALUES ( :userId,:repoName)";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':userId' => $userId, ':repoName' =>$repoName));
        $result = $db->lastInsertId();
        Database::closeConnection($db);
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return $result;
}

function getBranchFromDBByRepoIdAndBranchName($repoId, $branchName)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM branch WHERE repoId=:repoId AND `name`=:name AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':repoId' => $repoId, ':name' => $branchName));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function updateBranch($branchId, $lastCommitSHA, $lastCommitDatetime)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "UPDATE branch SET lastCommitSHA=:lastCommitSHA, lastCommitDatetime=:lastCommitDatetime WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $branchId, ':lastCommitSHA' => $lastCommitSHA, ':lastCommitDatetime' => $lastCommitDatetime));
        Database::closeConnection($db);
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}


function getCommitFromDBByCommitSHA($commitSHA)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM `commit` WHERE commitSHA=:commitSHA AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':commitSHA' => $commitSHA));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function createTask($branchId,$commitSHA)
{   
    $result = false;
    try {
        
        $db = Database::openConnection();
        $sqlString = "INSERT INTO fetchProcess (`branchId`,`commitSHA`) VALUES ( :branchId, :commitSHA)";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':branchId' => $branchId, ':commitSHA' => $commitSHA));
        $result = $db->lastInsertId();
        Database::closeConnection($db);
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return $result;
}

function getTask($status)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM `fetchProcess` WHERE `status`=:status AND isActive=1 AND isDeleted=0 LIMIT 1";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':status' => $status));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function getTaskByTaskId($id,$status)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT * FROM `fetchProcess` WHERE `id`=:id AND `status`=:status AND isActive=1 AND isDeleted=0 LIMIT 1";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':id' => $id, ':status' => $status));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}



function lockTask($id,$comment="")
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "UPDATE fetchProcess SET status=1,`comment`=:comment WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':comment'=>$comment));
        Database::closeConnection($db);
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}

function updateTask($id,$commitSHA,$comment="")
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "UPDATE fetchProcess SET status=0, commitSHA=:commitSHA, nextPageNumber=nextPageNumber+1,`comment`=:comment  WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':commitSHA'=>$commitSHA,':comment'=>$comment));
        Database::closeConnection($db);
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}

function markDoneTask($id,$comment="")
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "UPDATE fetchProcess SET status=2,`comment`=:comment  WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':comment'=>$comment));
        Database::closeConnection($db);
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}

/**
 * 
 * 
 *      
        SELECT * FROM `commit` AS C
        INNER JOIN `branch` AS B ON C.branchId = B.id
        INNER JOIN `repo` AS R ON B.repoId = R.id
        WHERE R.id=1 AND C.commitDatetime BETWEEN '2019-08-14 00:00:00' AND '2022-08-30 23:59:59' 
        AND C.isActive=1 AND C.isDeleted=0
 */

function getCommitsForRepoByDateRange($repoId,$startDate,$endDate)
{
    try {
        
        $db = Database::openConnection();
        $sqlString = "SELECT C.id, R.name AS repoName, B.name AS branchName, C.committerName, C.committerEmail, C.commitDatetime, C.commitMessage 
        FROM `commit` AS C
        INNER JOIN `branch` AS B ON C.branchId = B.id
        INNER JOIN `repo` AS R ON B.repoId = R.id
        WHERE R.id=:repoId AND C.commitDatetime BETWEEN :startDate AND :endDate 
        AND C.isActive=1 AND C.isDeleted=0
        ORDER BY C.commitDatetime DESC";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':repoId' => $repoId, ':startDate' => $startDate, ':endDate' => $endDate));
        $result = [];
        while ($row = $stm->fetch()) {
            $result[] = $row;
        }
        Database::closeConnection($db);
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

//$token = prompt_silent("Token:");
function prompt_silent($prompt = "Enter Password:") {
    if (preg_match('/^win/i', PHP_OS)) {
      $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
      file_put_contents(
        $vbscript, 'wscript.echo(InputBox("'
        . addslashes($prompt)
        . '", "", "password here"))');
      $command = "cscript //nologo " . escapeshellarg($vbscript);
      $password = rtrim(shell_exec($command));
      unlink($vbscript);
      return $password;
    } else {
      $command = "/usr/bin/env bash -c 'echo OK'";
      if (rtrim(shell_exec($command)) !== 'OK') {
        trigger_error("Can't invoke bash");
        return;
      }
      $command = "/usr/bin/env bash -c 'read -s -p \""
        . addslashes($prompt)
        . "\" mypassword && echo \$mypassword'";
      $password = rtrim(shell_exec($command));
      echo "\n";
      return $password;
    }
  }
  