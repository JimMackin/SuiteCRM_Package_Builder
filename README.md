# SuiteCRM_Package_Builder
SuiteCRMPackageBuilder is a small PHP script which builds up the zip package for a SuiteCRM module based on a compliant manifest file.

##Usage

suitecrmpackagebuilder simply requires the path to the manifest file, the path to the SuiteCRM instance and a path for the newly created package.

`suitecrmpackagebuilder <manifestpath> <suitecrmpath> <zippath>`

i.e.

`suitecrmpackagebuilder /var/www/mySuiteCRMInstance/manifest.php /var/www/mySuiteCRMInstance/ SuiteCRMPackage.zip`
