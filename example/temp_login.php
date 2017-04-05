<?php
require '../sarshomar-sdk-v1.php';

$sarshomar = new \sarshomar\sdk(array(
	'api_token' => '$2y$07$mNMRd07JHIr/bJKdFGKU0.Vv/eSPM83VNZ.AooKt0ufPAptCf99VO',
	'version' => 1
	));

if(isset($_GET['token']))
{
	$res = $sarshomar->get('token/login', ['temp_token' => $_GET['token']]);
	var_dump($res);
}
else
{
	$res = $sarshomar->get('token/temp');
	if($sarshomar->error())
	{
		var_dump($sarshomar->error());
	}
	else
	{
		echo "<a href='" . $sarshomar->token_link($res['token']) . "'>get access</a>";
	}
}
?>