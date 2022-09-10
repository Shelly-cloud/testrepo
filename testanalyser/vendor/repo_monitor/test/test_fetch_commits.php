<?php

require_once __dir__ . "/../src/app/pull_commits.php";

$githubUsername = "shelly-cloud";
$repoName = "testrepo";
$githubAuthToken = "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";


$fetchCommitsResult = fetchRepoWithCommits($githubUsername, $repoName, $githubAuthToken);
print_r($fetchCommitsResult);
