<?php
/*
 * Contains the API v2
 */
class FSLM_APIv2_Responses {

	const INVALID_LICENSE_KEY = array(
		"result" => "error",
		"code" => "100",
		"message" => "Invalid license key"
	);

	const INVALID_API_KEY = array(
		"result" => "error",
		"code" => "200",
		"message" => "Invalid API key"
	);

	const LICENSE_KEY_ACTIVATED = array(
		"result" => "success",
		"code" => "300",
		"message" => "License key activated"
	);

	const ACTIVATION_MAX_REACHED = array(
		"result" => "error",
		"code" => "350",
		"message" => "Maximum number of activation reached"
	);

	const LICENSE_KEY_DEACTIVATED = array(
		"result" => "success",
		"code" => "400",
		"message" => "License key deactivated"
	);

	const LICENSE_ALREADY_INACTIVE = array(
		"result" => "success",
		"code" => "450",
		"message" => "License key already inactive"
	);

	const VALID = array(
		"result" => "success",
		"code" => "500",
		"message" => "Valid license key"
	);

	const EXPIRED = array(
		"result" => "error",
		"code" => "550",
		"message" => "Expired license key"
	);

	const ERROR = array(
		"result" => "error",
		"code" => "000",
		"message" => "An error has occurred please retry"
	);

	const INVALID_PARAMETERS = array(
		"result" => "error",
		"code" => "600",
		"message" => "Invalid parameters"
	);

	const INVALID_DEVICE_ID = array(
		"result" => "error",
		"code" => "650",
		"message" => "Invalid device ID"
	);
	
	
	const DEVICE_ID_REQUIRED_DEACTIVATION = array(
		"result" => "error",
		"code" => "700",
		"message" => "Device ID required, this license keys was activated with a device ID, a device ID is required to deactivate it"
	);
	
	const DEVICE_ID_REQUIRED_ACTIVATION = array(
		"result" => "error",
		"code" => "750",
		"message" => "Device ID required, this license keys was activated with a device ID, a device ID is required to activate it again"
	);


}
