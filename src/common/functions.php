<?php
require_once __dir__ . "/../core/Database.php";
require_once __dir__ . "/../core/Logger.php";


function getBranchFromDBByRepoIdAndBranchName($repoId, $branchName)
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "SELECT * FROM branch WHERE repoId=:repoId AND `name`=:name AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':repoId' => $repoId, ':name' => $branchName));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        $database->closeConnection();
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function updateBranch($branchId, $lastCommitSHA, $lastCommitDatetime)
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "UPDATE branch SET lastCommitSHA=:lastCommitSHA, lastCommitDatetime=:lastCommitDatetime WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $branchId, ':lastCommitSHA' => $lastCommitSHA, ':lastCommitDatetime' => $lastCommitDatetime));
        $database->closeConnection();
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}


function getCommitFromDBByCommitSHA($commitSHA)
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "SELECT * FROM `commit` WHERE commitSHA=:commitSHA AND isActive=1 AND isDeleted=0";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':commitSHA' => $commitSHA));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        $database->closeConnection();
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function createTask($branchId,$commitSHA)
{   
    $result = false;
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "INSERT INTO fetchProcess (`branchId`,`commitSHA`) VALUES ( :branchId, :commitSHA)";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':branchId' => $branchId, ':commitSHA' => $commitSHA));
        $result = $db->lastInsertId();
        $database->closeConnection();
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return $result;
}
function getTask($status)
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "SELECT * FROM `fetchProcess` WHERE `status`=:status AND isActive=1 AND isDeleted=0 LIMIT 1";
        $stm = $db->prepare($sqlString);
        $stm->execute(array(':status' => $status));
        $result = [];
        if ($row = $stm->fetch()) {
            $result = $row;
        }
        $database->closeConnection();
        return $result;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
}

function lockTask($id,$comment="")
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "UPDATE fetchProcess SET status=1,`comment`=:comment WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':comment'=>$comment));
        $database->closeConnection();
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}
function updateTask($id,$commitSHA,$comment="")
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "UPDATE fetchProcess SET status=0, commitSHA=:commitSHA, nextPageNumber=nextPageNumber+1,`comment`=:comment  WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':commitSHA'=>$commitSHA,':comment'=>$comment));
        $database->closeConnection();
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}
function markDoneTask($id,$comment="")
{
    try {
        $database = new Database();
        $db = $database->openConnection();
        $sqlString = "UPDATE fetchProcess SET status=2,`comment`=:comment  WHERE id=:id";
        $stm = $db->prepare($sqlString);
        $affectedrows = $stm->execute(array(':id' => $id,':comment'=>$comment));
        $database->closeConnection();
        return $affectedrows;
    } catch (PDOException $e) {
        Logger::error("[" . __FUNCTION__ . "] Error while executing query[" . $sqlString . "] Error : " . $e->getMessage());
    }
    return false;
}
