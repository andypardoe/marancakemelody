
<h1>Anmeldung</h1>
<div id="login">
<?php 
if($error == "true") {
	echo "<p>Die Login-Informationen konnten nicht verifiziert werden, kontrollieren Sie ihre Daten und versuchen Sie es erneut</p>";
}
?>
<form method="post" action="<?php echo $html->url('/users/login') ?>">
		Username<br />
		<?php echo $form->text('User/username', array('size' => '15', 'class' => 'input_medium')); ?><br /><br />
		Passwort<br />
		<?php echo $form->password('User/password', array('size' => '15', 'class' => 'input_medium')); ?><br />
		<?php echo $form->submit('Anmelden'); ?>
</form>
</div>
<div id="login_foot">
<?php echo $html->link('[Registrieren]', '/users/register'); ?>
<?php //echo $html->link('[Passwort vergessen?]', '/users/forgot_password'); ?>
</div>

