<?php
// when we're processing the template, get the 'target' from the template variables
if (isset($panel_name))	{
	$target = $template_variables[$panel_name]['target'];
}
// Contact Form
$locale['400'] = "Contact ".$settings['siteusername'];
$locale['401'] = "<b>There are many ways you can contact ".$settings['siteusername']."</b>.<br /><br />You can Email us directly at
<a href='mailto:".str_replace("@","&#64;",$target)."'>".str_replace("@","&#64;",$target)."</a>.<br /><br />
If you are a Member you can send the webmaster a <a href='messages.php?msg_send=1'>Private Message</a>.<br /><br />
Alternatively, you can fill in this form, this sends your message to us via Email.";
$locale['402'] = "Name:";
$locale['403'] = "Email Address:";
$locale['404'] = "Subject:";
$locale['405'] = "Message:";
$locale['406'] = "Send Message";
// Contact Errors
$locale['420'] = "You must specify a Name";
$locale['421'] = "You must specify an Email Address";
$locale['422'] = "You must specify a Subject";
$locale['423'] = "You must specify a Message";
// Message Sent
$locale['440'] = "Your Message has been sent";
$locale['441'] = "Thank You";
$locale['442'] = "Your message was not sent for the following reason(s):";
$locale['443'] = "Please try again.";
?>