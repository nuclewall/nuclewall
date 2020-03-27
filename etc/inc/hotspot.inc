<?php
/*
	hotspot.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

$image_file = '/usr/local/www/pages/img/captiveportal-nuclewall';
$js_file = '/usr/local/captiveportal/pages/js/captiveportal-locale.js';
$js_mobile_file = '/usr/local/captiveportal/pages/js/captiveportal-locale-mobile.js';

$image_cache = filemtime($image_file);

if(file_exists($js_file))
	$js_cache = filemtime($js_file);

if(file_exists($js_mobile_file))
	$js_mobile_cache = filemtime($js_mobile_file);

function createHtml()
{
	$html = <<<EOD
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="cache-control" content="max-age=0">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
<meta http-equiv="pragma" content="no-cache">

<title id="main_title"></title>
<link type="text/css" rel="stylesheet" href="pages/css/captiveportal-bootstrap.css">
<script type="text/javascript" src="pages/js/captiveportal-jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="pages/js/captiveportal-bootstrap.min.js"></script>
</head>

<body>
<hr>
<div class="container">
	<div class="row">
		<div class="col-lg-offset-3 col-xs-12 col-lg-6">
			<div class="row">
				<div class="col-xs-4 col-sm-4">
					<img src="pages/img/captiveportal-nuclewall?cache={$GLOBALS['image_cache']}" class="img-thumbnail">
				</div>
				<div style="padding-left:0;" class="col-xs-8 col-sm-8 text-right">
					<h5 id="title"></h5>
					<nav style="margin-top:15px;">
						<ul style="margin:0;" class="pagination pagination-sm">
						    <li id="en" class="active"><a title="English" href="#">English</a></li>
							<li id="tr"><a title="Türkçe" href="#">Türkçe</a></li>
							<li id="de"><a title="Deutsche" href="#">Deutsche</a></li>
							<li id="ru"><a title="Русский" href="#">Русский</a></li>
						</ul>
					</nav>
				</div>
			</div>
		</div>
	</div>
</div>
<hr>
<div class="container">
	<div class="row">
		<div class="col-lg-offset-3 col-xs-12 col-lg-6">
			<div class="jumbotron">
				<div class="row text-left">
					<div class="text-left ">
						<form method="post" role="form" action="#PORTAL_ACTION#" class="col-xs-12 col-sm-12 text-left">
							<input name="redirurl" type="hidden" value="#PORTAL_REDIRURL#">
							<div class="form-group">
								<label id="username" for="auth_user"></label>
								<input type="text" required class="form-control" id="auth_user" name="auth_user">
							</div>
							<div class="form-group">
								<label id="password" for="auth_pass"></label>
								<input type="password" required class="form-control" id="auth_pass" name="auth_pass">
							</div>
							<div class="form-group">
								<input name="accept" type="submit" id="button" class="btn btn-primary" style="margin-top: 10px;">
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<hr>
<footer class="text-center">
  <div class="container">
    <div class="row">
      <div class="col-xs-12">
        <p><a target="_blank" href="https://nuclewall.github.io/nuclewall/index_en.html">NUCLEWALL</a></p>
      </div>
    </div>
  </div>
</footer>
<script type="text/javascript" src="pages/js/captiveportal-locale.js?cache={$GLOBALS['js_cache']}"></script>
</body>
</html>

EOD;

	$file = "/usr/local/captiveportal/pages/hotspot.html";
	$file_demo = "/usr/local/www/hotspot.html";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createHtmlErr()
{
	$html = <<<EOD
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="cache-control" content="max-age=0">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
<meta http-equiv="pragma" content="no-cache">

<title id="main_title"></title>
<link type="text/css" rel="stylesheet" href="pages/css/captiveportal-bootstrap.css">
<script type="text/javascript" src="pages/js/captiveportal-jquery-1.11.2.min.js"></script>
<script type="text/javascript" src="pages/js/captiveportal-bootstrap.min.js"></script>
</head>

<body>
<hr>
<div class="container">
	<div class="row">
		<div class="col-lg-offset-3 col-xs-12 col-lg-6">
			<div class="row">
				<div class="col-xs-4 col-sm-4">
					<img src="pages/img/captiveportal-nuclewall?cache={$GLOBALS['image_cache']}" class="img-thumbnail">
				</div>
				<div style="padding-left:0;" class="col-xs-8 col-sm-8 text-right">
					<h5 id="title"></h5>
					<nav style="margin-top:15px;">
						<ul style="margin:0;" class="pagination pagination-sm">
							<li id="en" class="active"><a title="English" href="#">English</a></li>
							<li id="tr"><a title="Türkçe" href="#">Türkçe</a></li>
							<li id="de"><a title="Deutsche" href="#">Deutsche</a></li>
							<li id="ru"><a title="Русский" href="#">Русский</a></li>
						</ul>
					</nav>
				</div>
			</div>
		</div>
	</div>
</div>
<hr>
<div class="container">
	<div class="row">
		<div class="col-lg-offset-3 col-xs-12 col-lg-6">
			<div class="jumbotron">
				<div class="row text-left">
					<div class="text-left ">
						<form method="post" role="form" action="#PORTAL_ACTION#" class="col-xs-12 col-sm-12 text-left">
							<input name="redirurl" type="hidden" value="#PORTAL_REDIRURL#">
							<div class="form-group">
								<label id="username" for="auth_user"></label>
								<input type="text" required class="form-control" id="auth_user" name="auth_user">
							</div>
							<div class="form-group">
								<label id="password" for="auth_pass"></label>
								<input type="password" required class="form-control" id="auth_pass" name="auth_pass">
							</div>
							<div class="form-group">
								<input name="accept" type="submit" id="button" class="btn btn-primary" style="margin-top: 10px;">
							</div>
							<span class="help-block"><div class="alert alert-danger" role="alert">#PORTAL_MESSAGE#</div></span>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<hr>
<footer class="text-center">
  <div class="container">
    <div class="row">
      <div class="col-xs-12">
        <p><a target="_blank" href="https://nuclewall.github.io/nuclewall/index_en.html">NUCLEWALL</a></p>
      </div>
    </div>
  </div>
</footer>
<script type="text/javascript" src="pages/js/captiveportal-locale.js?cache={$GLOBALS['js_cache']}"></script>
</body>
</html>

EOD;

	$file = "/usr/local/captiveportal/pages/hotspot_err.html";
	$file_demo = "/usr/local/www/hotspot_err.html";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createMobileHtml()
{
	$html = <<<EOD
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="cache-control" content="max-age=0">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
<meta http-equiv="pragma" content="no-cache">

<title id="main_title"></title>
<link href="pages/css/captiveportal-jquery.mobile-1.4.5.min.css" rel="stylesheet" type="text/css">
<script src="pages/js/captiveportal-jquery-1.11.2.min.js" type="text/javascript"></script>
<script src="pages/js/captiveportal-jquery.mobile-1.4.5.min.js" type="text/javascript"></script>
</head>

<body>

<div data-role="page" id="page1">
	<div data-theme="b" data-role="header">
		<h1 id="company" style="margin:5px;"></h1>
	</div>
	<div style="text-align:center; margin-top:2%;" class="ui-bar">
		<h1 id="title"></h1>
	</div>
	<div style="padding-top:0;" role="main" class="ui-content">
		<form data-ajax="false" method="post" role="form" action="#PORTAL_ACTION#">
			<input name="redirurl" type="hidden" value="#PORTAL_REDIRURL#">
			<div style="padding-top:0;" data-role="fieldcontain">
				<input type="text" required id="auth_user" data-autogrow="false" name="auth_user">
				<input  type="password" required id="auth_pass" name="auth_pass" >
				<input data-icon="check" data-iconpos="right" name="accept" type="submit" id="button">
			</div>
		</form>
        <center>
		<fieldset data-role="controlgroup" data-type="vertical">
		    <input type="radio" name="radio-mini" id="en" value="en" checked="checked">
			<label for="en">English</label>

			<input type="radio" name="radio-mini" id="tr" value="tr">
			<label for="tr">Türkçe</label>

			<input type="radio" name="radio-mini" id="de" value="de">
			<label for="de">Deutsche</label>

			<input type="radio" name="radio-mini" id="ru" value="ru">
			<label for="ru">Русский</label>
		</fieldset>
        </center>
	</div>
	<div style="text-align:center;margin-top:20px;" data-theme="b" data-role="footer">
		<a style="font-size:11px;" href="https://nuclewall.github.io/nuclewall/index_en.html" target="_blank" data-role="button">NUCLEWALL</a>
	</div>
</div>
<script type="text/javascript" src="pages/js/captiveportal-locale-mobile.js?cache={$GLOBALS['js_mobile_cache']}"></script>
</body>
</html>

EOD;

	$file = "/usr/local/captiveportal/pages/hotspot_mobile.html";
	$file_demo = "/usr/local/www/hotspot_mobile.html";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createMobileHtmlErr()
{
	$html = <<<EOD
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="cache-control" content="max-age=0">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
<meta http-equiv="pragma" content="no-cache">

<title id="main_title"></title>
<link href="pages/css/captiveportal-jquery.mobile-1.4.5.min.css" rel="stylesheet" type="text/css">
<script src="pages/js/captiveportal-jquery-1.11.2.min.js" type="text/javascript"></script>
<script src="pages/js/captiveportal-jquery.mobile-1.4.5.min.js" type="text/javascript"></script>
</head>

<body>

<div data-role="page" id="page1">
	<div data-theme="b" data-role="header">
		<h1 id="company" style="margin:5px;"></h1>
	</div>
	<div style="text-align:center; margin-top:2%;" class="ui-bar">
		<h1 id="title"></h1>
	</div>
	<div style="padding-top:0;" role="main" class="ui-content">
		<form data-ajax="false" method="post" role="form" action="#PORTAL_ACTION#">
			<input name="redirurl" type="hidden" value="#PORTAL_REDIRURL#">
			<div style="padding-top:0;" data-role="fieldcontain">
				<input type="text" required id="auth_user" data-autogrow="false" name="auth_user">
				<input  type="password" required id="auth_pass" name="auth_pass" >
				<input data-icon="check" data-iconpos="right" name="accept" type="submit" id="button">
			</div>

			<div style="background-color:#F2DEDE;border-color:#EBCCD1;color:#A94442" data-role="popup" id="myPopup" class="ui-content">
				<a href="#" data-rel="back" class="ui-btn ui-corner-all ui-shadow ui-btn ui-icon-delete ui-btn-icon-notext ui-btn-left">Close</a>
				<p><b>#PORTAL_MESSAGE#</b></p>
			</div>
		</form>
	</div>
	<center>
		<fieldset data-role="controlgroup" data-type="vertical">
     		<input type="radio" name="radio-mini" id="en" value="en" checked="checked">
	    	<label for="en">English</label>

			<input type="radio" name="radio-mini" id="tr" value="tr">
			<label for="tr">Türkçe</label>

			<input type="radio" name="radio-mini" id="de" value="de">
			<label for="de">Deutsche</label>

			<input type="radio" name="radio-mini" id="ru" value="ru">
			<label for="ru">Русский</label>
		</fieldset>
	</center>
	<div style="text-align:center;margin-top:20px;" data-theme="b" data-role="footer">
		<a style="font-size:11px;" href="https://nuclewall.github.io/nuclewall/index_en.html" target="_blank" data-role="button">NUCLEWALL</a>
	</div>
</div>

<script type="text/javascript" src="pages/js/captiveportal-locale-mobile.js?cache={$GLOBALS['js_mobile_cache']}"></script>
</body>
</html>

EOD;

	$file = "/usr/local/captiveportal/pages/hotspot_mobile_err.html";
	$file_demo = "/usr/local/www/hotspot_mobile_err.html";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createDefaultJS($default_lang)
{
	$html = <<<EOD
/*
	captiveportal-locale.js
	This page is created automatically.

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

var default_lang = "$default_lang";

var title_tr = "İnternet erişimi için giriş yapın";
var title_en = "Log in to access the Internet";
var title_de = "Anmelden um auf das Internet zuzugreifen";
var title_ru = " Логин для доступа в Интернет";

var uname_tr = "Kullanıcı adı";
var uname_en = "Username";
var uname_de = "Benutzername";
var uname_ru = "Имя пользователя";

var pass_tr = "Parola";
var pass_en = "Password";
var pass_de = "Passwort"
var pass_ru = "Пароль";

var button_tr = "Giriş Yap";
var button_en = "Log In";
var button_de = "Anmelden";
var button_ru = "Вход";

function setLang(lang)
{
	switch (lang)
	{
		case "tr":
			$("#main_title").text(title_tr);
			$("#title").text(title_tr);
			$("#username").text(uname_tr);
			$("#password").text(pass_tr);
			$("#button").val(button_tr);

			$("li").removeClass("active");
			$("li#tr").addClass("active");
			document.cookie = "user_lang=tr";
			localStorage.setItem("user_lang", "tr");
			break;

		case "en":
			$("#main_title").text(title_en);
			$("#title").text(title_en);
			$("#username").text(uname_en);
			$("#password").text(pass_en);
			$("#button").val(button_en);

			$("li").removeClass("active");
			$("li#en").addClass("active");
			document.cookie = "user_lang=en";
			localStorage.setItem("user_lang", "en");
			break;

		case "de":
			$("#main_title").text(title_de);
			$("#title").text(title_de);
			$("#username").text(uname_de);
			$("#password").text(pass_de);
			$("#button").val(button_de);

			$("li").removeClass("active");
			$("li#de").addClass("active");
			document.cookie = "user_lang=de";
			localStorage.setItem("user_lang", "de");
			break;

		case "ru":
			$("#main_title").text(title_ru);
			$("#title").text(title_ru);
			$("#username").text(uname_ru);
			$("#password").text(pass_ru);
			$("#button").val(button_ru);

			$("li").removeClass("active");
			$("li#ru").addClass("active");
			document.cookie = "user_lang=ru";
			localStorage.setItem("user_lang", "ru");
			break;
	}
}

function setDefault()
{
	var user_lang = localStorage.getItem("user_lang");

	if(user_lang)
		setLang(user_lang);
	else
		setLang(default_lang);
}

$(document).ready(setDefault);
$('#tr').on('click', function() { setLang('tr');});
$('#en').on('click', function() { setLang('en');});
$('#de').on('click', function() { setLang('de');});
$('#ru').on('click', function() { setLang('ru');});

EOD;

	$file = "/usr/local/captiveportal/pages/js/captiveportal-locale.js";
	$file_demo = "/usr/local/www/pages/js/captiveportal-locale.js";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createDefaultMobileJS($default_lang, $company)
{
	$html = <<<EOD
/*
	captiveportal-locale-mobile.js
	This page is created automatically.

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

var default_lang = "$default_lang";
var company = "$company";

var title_tr = "İnternet erişimi için giriş yapın";
var title_en = "Log in to access the Internet";
var title_de = "Anmelden um auf das Internet zuzugreifen";
var title_ru = " Логин для доступа в Интернет";

var uname_tr = "Kullanıcı adı";
var uname_en = "Username";
var uname_de = "Benutzername";
var uname_ru = "Имя пользователя";

var pass_tr = "Parola";
var pass_en = "Password";
var pass_de = "Passwort"
var pass_ru = "Пароль";

var button_tr = "Giriş Yap";
var button_en = "Log In";
var button_de = "Anmelden";
var button_ru = "Вход";

function setLang(lang)
{
	switch (lang)
	{
		case "tr":
			$("#main_title").text(title_tr);
			$("#title").text(title_tr);
			$("#auth_user").attr("placeholder", uname_tr);
			$("#auth_pass").attr("placeholder", pass_tr);
			$("#button").val(button_tr);
			document.cookie = "user_lang=tr";
			localStorage.setItem("user_lang", "tr");
			break;

		case "en":
			$("#main_title").text(title_en);
			$("#title").text(title_en);
			$("#auth_user").attr("placeholder", uname_en);
			$("#auth_pass").attr("placeholder", pass_en);
			$("#button").val(button_en);
			document.cookie = "user_lang=en";
			localStorage.setItem("user_lang", "en");
			break;

		case "de":
			$("#main_title").text(title_de);
			$("#title").text(title_de);
			$("#auth_user").attr("placeholder", uname_de);
			$("#auth_pass").attr("placeholder", pass_de);
			$("#button").val(button_de);
			document.cookie = "user_lang=de";
			localStorage.setItem("user_lang", "de");
			break;

		case "ru":
			$("#main_title").text(title_ru);
			$("#title").text(title_ru);
			$("#auth_user").attr("placeholder", uname_ru);
			$("#auth_pass").attr("placeholder", pass_ru);
			$("#button").val(button_ru);
			document.cookie = "user_lang=ru";
			localStorage.setItem("user_lang", "ru");
			break;
	}

	$("#company").text(company);
	$("#button").button("refresh");
}

function setDefault()
{
	var user_lang = localStorage.getItem("user_lang");

	if(user_lang)
	{
		setLang(user_lang);
		$("#"+user_lang).prop("checked", true);
	}
	else
	{
		setLang(default_lang);
		$("#"+default_lang).prop("checked", true);
	}

	$("input[type='radio']").checkboxradio("refresh");
}

$("input[type='radio']").on("change", function(e)
{
	setLang($(this).val());
});

$(document).ready(setDefault);

$(":jqmData(role='page'):last").on("pageshow", function(event)
{
	$("#myPopup", $(this)).popup("open");
});

EOD;

	$file = "/usr/local/captiveportal/pages/js/captiveportal-locale-mobile.js";
	$file_demo = "/usr/local/www/pages/js/captiveportal-locale-mobile.js";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createCustomJS($default_lang, $ops)
{
	$tr_enabled = isset($ops['tr']['enabled']);
	$en_enabled = isset($ops['en']['enabled']);
	$de_enabled = isset($ops['de']['enabled']);
	$ru_enabled = isset($ops['ru']['enabled']);

	$html = <<<EOD
/*
	captiveportal-locale.js
	This page is created automatically.

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

var default_lang = "$default_lang";

EOD;

	if($tr_enabled)
	{
	$title = base64_decode($ops['tr']['title']);
	$uname = base64_decode($ops['tr']['uname']);
	$pass = base64_decode($ops['tr']['password']);
	$button = base64_decode($ops['tr']['button']);

	$html .= <<<EOD
var title_tr = "$title";
var uname_tr = "$uname";
var pass_tr = "$pass";
var button_tr = "$button";


EOD;

	}

	if($en_enabled)
	{
	$title = base64_decode($ops['en']['title']);
	$uname = base64_decode($ops['en']['uname']);
	$pass = base64_decode($ops['en']['password']);
	$button = base64_decode($ops['en']['button']);

		$html .= <<<EOD
var title_en = "$title";
var uname_en = "$uname";
var pass_en = "$pass";
var button_en = "$button";


EOD;
	}

	if($de_enabled)
	{
	$title = base64_decode($ops['de']['title']);
	$uname = base64_decode($ops['de']['uname']);
	$pass = base64_decode($ops['de']['password']);
	$button = base64_decode($ops['de']['button']);

		$html .= <<<EOD
var title_de = "$title";
var uname_de = "$uname";
var pass_de = "$pass";
var button_de = "$button";


EOD;
	}

	if($ru_enabled)
	{
	$title = base64_decode($ops['ru']['title']);
	$uname = base64_decode($ops['ru']['uname']);
	$pass = base64_decode($ops['ru']['password']);
	$button = base64_decode($ops['ru']['button']);

		$html .= <<<EOD
var title_ru= "$title";
var uname_ru = "$uname";
var pass_ru = "$pass";
var button_ru = "$button";


EOD;
	}

	$html .= <<<EOD
function setLang(lang)
{
	switch (lang)
	{

EOD;

	if($tr_enabled)
	{
		$html .= <<<EOD
		case "tr":
			$("#main_title").text(title_tr);
			$("#title").text(title_tr);
			$("#username").text(uname_tr);
			$("#password").text(pass_tr);
			$("#button").val(button_tr);

			$("li").removeClass("active");
			$("li#tr").addClass("active");
			document.cookie = "user_lang=tr";
			localStorage.setItem("user_lang", "tr");
			break;

EOD;
	}

	if($en_enabled)
	{
		$html .= <<<EOD
		case "en":
			$("#main_title").text(title_en);
			$("#title").text(title_en);
			$("#username").text(uname_en);
			$("#password").text(pass_en);
			$("#button").val(button_en);

			$("li").removeClass("active");
			$("li#en").addClass("active");
			document.cookie = "user_lang=en";
			localStorage.setItem("user_lang", "en");
			break;

EOD;
	}

	if($de_enabled)
	{
		$html .= <<<EOD
		case "de":
			$("#main_title").text(title_de);
			$("#title").text(title_de);
			$("#username").text(uname_de);
			$("#password").text(pass_de);
			$("#button").val(button_de);

			$("li").removeClass("active");
			$("li#de").addClass("active");
			document.cookie = "user_lang=de";
			localStorage.setItem("user_lang", "de");
			break;

EOD;
	}

	if($ru_enabled)
	{
		$html .= <<<EOD
		case "ru":
			$("#main_title").text(title_ru);
			$("#title").text(title_ru);
			$("#username").text(uname_ru);
			$("#password").text(pass_ru);
			$("#button").val(button_ru);

			$("li").removeClass("active");
			$("li#ru").addClass("active");
			document.cookie = "user_lang=ru";
			localStorage.setItem("user_lang", "ru");
			break;

EOD;
	}

	$html .= <<<EOD

	}
}

function setDefault()
{
	var user_lang = localStorage.getItem("user_lang");

	if(user_lang)
		setLang(user_lang);
	else
		setLang(default_lang);
}

$(document).ready(setDefault);

EOD;

	if($tr_enabled)
		$html .= "$('#tr').on('click', function() { setLang('tr');});\n";
	else
		$html .= "$('li#tr').addClass('disabled');\n";

	if($en_enabled)
		$html .= "$('#en').on('click', function() { setLang('en');});\n";
	else
		$html .= "$('li#en').addClass('disabled');\n";

	if($de_enabled)
		$html .= "$('#de').on('click', function() { setLang('de');});\n";
	else
		$html .= "$('li#de').addClass('disabled');\n";

	if($ru_enabled)
		$html .= "$('#ru').on('click', function() { setLang('ru');});\n";
	else
		$html .= "$('li#ru').addClass('disabled');\n";

	$file = "/usr/local/captiveportal/pages/js/captiveportal-locale.js";
	$file_demo = "/usr/local/www/pages/js/captiveportal-locale.js";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function createCustomMobileJS($default_lang, $company, $ops)
{
	$tr_enabled = isset($ops['tr']['enabled']);
	$en_enabled = isset($ops['en']['enabled']);
	$de_enabled = isset($ops['de']['enabled']);
	$ru_enabled = isset($ops['ru']['enabled']);

	$html = <<<EOD
/*
	captiveportal-locale-mobile.js
	This page is created automatically.

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

var default_lang = "$default_lang";
var company = "$company";


EOD;

	if($tr_enabled)
	{
	$title = base64_decode($ops['tr']['title']);
	$uname = base64_decode($ops['tr']['uname']);
	$pass = base64_decode($ops['tr']['password']);
	$button = base64_decode($ops['tr']['button']);

	$html .= <<<EOD
var title_tr = "$title";
var uname_tr = "$uname";
var pass_tr = "$pass";
var button_tr = "$button";


EOD;

	}

	if($en_enabled)
	{
	$title = base64_decode($ops['en']['title']);
	$uname = base64_decode($ops['en']['uname']);
	$pass = base64_decode($ops['en']['password']);
	$button = base64_decode($ops['en']['button']);

		$html .= <<<EOD
var title_en = "$title";
var uname_en = "$uname";
var pass_en = "$pass";
var button_en = "$button";


EOD;
	}

	if($de_enabled)
	{
	$title = base64_decode($ops['de']['title']);
	$uname = base64_decode($ops['de']['uname']);
	$pass = base64_decode($ops['de']['password']);
	$button = base64_decode($ops['de']['button']);

		$html .= <<<EOD
var title_de = "$title";
var uname_de = "$uname";
var pass_de = "$pass";
var button_de = "$button";


EOD;
	}

	if($ru_enabled)
	{
	$title = base64_decode($ops['ru']['title']);
	$uname = base64_decode($ops['ru']['uname']);
	$pass = base64_decode($ops['ru']['password']);
	$button = base64_decode($ops['ru']['button']);

		$html .= <<<EOD
var title_ru= "$title";
var uname_ru = "$uname";
var pass_ru = "$pass";
var button_ru = "$button";


EOD;
	}

		$html .= <<<EOD

function setLang(lang)
{
	switch (lang)
	{

EOD;

	if($tr_enabled)
	{
		$html .= <<<EOD
		case "tr":
			$("#main_title").text(title_tr);
			$("#title").text(title_tr);
			$("#auth_user").attr("placeholder", uname_tr);
			$("#auth_pass").attr("placeholder", pass_tr);
			$("#button").val(button_tr);
			document.cookie = "user_lang=tr";
			localStorage.setItem("user_lang", "tr");
			break;

EOD;
	}

	if($en_enabled)
	{
		$html .= <<<EOD
		case "en":
			$("#main_title").text(title_en);
			$("#title").text(title_en);
			$("#auth_user").attr("placeholder", uname_en);
			$("#auth_pass").attr("placeholder", pass_en);
			$("#button").val(button_en);
			document.cookie = "user_lang=en";
			localStorage.setItem("user_lang", "en");
			break;

EOD;
	}

	if($de_enabled)
	{
		$html .= <<<EOD
		case "de":
			$("#main_title").text(title_de);
			$("#title").text(title_de);
			$("#auth_user").attr("placeholder", uname_de);
			$("#auth_pass").attr("placeholder", pass_de);
			$("#button").val(button_de);
			document.cookie = "user_lang=de";
			localStorage.setItem("user_lang", "de");
			break;

EOD;
	}

	if($ru_enabled)
	{
		$html .= <<<EOD
		case "ru":
			$("#main_title").text(title_ru);
			$("#title").text(title_ru);
			$("#auth_user").attr("placeholder", uname_ru);
			$("#auth_pass").attr("placeholder", pass_ru);
			$("#button").val(button_ru);
			document.cookie = "user_lang=ru";
			localStorage.setItem("user_lang", "ru");
			break;

EOD;
	}

	$html .= <<<EOD
	}
	$("#company").text(company);
	$("#button").button("refresh");
}

function setDefault()
{
	var user_lang = localStorage.getItem("user_lang");

	if(user_lang)
	{
		setLang(user_lang);
		$("#"+user_lang).prop("checked", true);
	}
	else
	{
		setLang(default_lang);
		$("#"+default_lang).prop("checked", true);
	}


EOD;

	if(!$tr_enabled)
	{
		$html .= <<<EOD
$("input#tr").checkboxradio('disable');

EOD;
	}

	if(!$en_enabled)
	{
		$html .= <<<EOD
$("input#en").checkboxradio('disable');

EOD;
	}

	if(!$de_enabled)
	{
		$html .= <<<EOD
$("input#de").checkboxradio('disable');

EOD;
	}

	if(!$ru_enabled)
	{
		$html .= <<<EOD
$("input#ru").checkboxradio('disable');

EOD;
	}

	$html .= <<<EOD
	$("input[type='radio']").checkboxradio("refresh");
}

$("input[type='radio']").on("change", function(e)
{
	setLang($(this).val());
});

$(document).ready(setDefault);

$(":jqmData(role='page'):last").on("pageshow", function(event)
{
	$("#myPopup", $(this)).popup("open");
});

EOD;

	$file = "/usr/local/captiveportal/pages/js/captiveportal-locale-mobile.js";
	$file_demo = "/usr/local/www/pages/js/captiveportal-locale-mobile.js";

	file_put_contents($file, $html);
	file_put_contents($file_demo, $html);
}

function initHtmlFiles()
{
	createHtml();
	createHtmlErr();
	createMobileHtml();
	createMobileHtmlErr();
}

function hotspot_init()
{
	$platform = trim(file_get_contents('/etc/platform'));
	$initFile = '/etc/nuclewall/hotspot_init';

	if(file_exists($initFile) and ($platform == 'pfSense'))
	{
		initHtmlFiles();
		createDefaultJS('en');
		createDefaultMobileJS('en', 'NUCLEWALL HOTSPOT');
		system("rm -f $initFile");
	}
}
?>
