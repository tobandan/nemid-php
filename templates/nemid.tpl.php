<html>
<body>
		<form id="signedForm" name="signedForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
				<div id="applet">
<!-- div ID used for overlay / modal-box -->
					<applet name="DANID_DIGITAL_SIGNATUR" tabindex="1" archive="<?= $ServerUrlPrefix ?>/bootapplet/1234567" code="dk.pbs.applet.bootstrap.BootApplet" width="200" height="250" mayscript="mayscript" style="visibility: visible; ">
						<param name="ServerUrlPrefix" value="<?= $ServerUrlPrefix ?>"> 
						<param name="ZIP_BASE_URL" value="<?= $ZIP_BASE_URL ?>">
						<param name="ZIP_FILE_ALIAS" value="<?= $ZIP_FILE_ALIAS ?>">
						<param name="log_level" value="<?= $log_level ?>"> 
						<param name="paramcert" value="<?= $paramcert ?>">
						<param name="signproperties" value="<?= $signproperties ?>"> 
						<param name="paramsdigest" value="<?= $paramsdigest ?>"> 
						<param name="signeddigest" value="<?= $signeddigest ?>"> 
						<param name="MAYSCRIPT" value="<?= $MAYSCRIPT ?>"> 
					</applet>
				</div>
			<input type="hidden" name="signature">
			<input type="hidden" name="result"> 
		</form>
<script type="text/javascript">
	function onLogonOk(signature) {
		document.signedForm.signature.value=signature;
		document.signedForm.result.value='ok';
		document.signedForm.submit();
	}
	function onLogonCancel() {
		document.signedForm.result.value='cancel';
		document.signedForm.submit();
	}
	function onLogonError(emsg) {
		document.signedForm.result.value=emsg;
		document.signedForm.submit();
	}
</script> 
</body>
</html>