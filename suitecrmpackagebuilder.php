#!/usr/bin/php
<?php
/**
 * Script to build a SuiteCRM package suitable for the SuiteCRM module installer.
 * Reads a given manifest file and generates the required zip.
 * @author Jim Mackin <Jim@JSMackin.co.uk>
 */

define('NAME',"SuiteCRM Package Builder");
define('VERSION','v0.1');

$pb = new SuiteCRMPackageBuilder($argv);
$pb->run();



class SuiteCRMPackageBuilder{
	private $script = '';
    private $targetManifest = '';
    private $targetZip = '';
    private $suitecrmBase = '';
    private $options = array();
    private $recognisedOptions = array('h','help','version');

	function __construct($args){
		$this->options = $this->getArguments($args);
	}

	private function getArguments($args){
		$opts = array();
		$this->script = array_shift($args);
        $posArgs = 0;
		foreach($args as $arg){
			if(substr($arg,0,2) == '--'){
				$opts[substr($arg,2)] = 1;
			}elseif(substr($arg,0,1) == '-'){
				foreach(str_split(substr($arg,1)) as $scOpt){
					$opts[$scOpt] = 1;
				}
			}else{
                if($posArgs === 0){
                    $this->targetManifest = $arg;
                }elseif($posArgs === 1){
                    $this->suitecrmBase = realpath($arg)."/";
                }else{
                    $this->targetZip = $arg;
                }
                $posArgs++;
			}
		}
		return $opts;
	}

    private function log($msg){
        echo $msg."\n";
    }

	public function run(){
		if(!$this->preFlight()){
            return;
		}

        $manifestDetails = $this->getManifestDetails();
        if($manifestDetails['manifest']['type'] !== 'module'){
            echo "suitecrmpackagebuilder only supports module packages currently.\n";
            return;
        }var_dump($this);
        //var_dump($manifestDetails);

        $zip = new ZipArchive();
        if ($zip->open($this->targetZip, ZipArchive::CREATE) !== true) {
            echo "cannot open <{$this->targetZip}>\n";
            return;
        }
        $this->log("Processing ".$manifestDetails['installdefs']['id'] ." - ".$manifestDetails['manifest']['name']);

        $this->processInstallDefs($manifestDetails['installdefs'],$zip);

        //TODO process upgrade manifest

        echo "numfiles: " . $zip->numFiles . "\n";
        echo "status:" . $zip->status . "\n";
        $zip->close();
	}

    private static function cleanPath($path){
        return str_ireplace('<basepath>','',$path);
    }

    private function addFilesFromArray($arr, $key, $zip){
        foreach($arr as $item){
            $path = self::cleanPath($item[$key]);
            if(!$path){
                $this->log("Skipping ".print_r($item,1));
                continue;
            }


            $res = $zip->addFile($this->suitecrmBase.$path,$path);
            if($res){
                $this->log("Added ".$this->suitecrmBase.$path);
            }else{
                $this->log("Failed to add ".$this->suitecrmBase.$path);
            }
        }
    }

    private function processInstallDefs($installdefs, $zip){

        $arr = array(
            'beans' => 'path',
            'language' => 'from',
            'vardefs' => 'from',
            'logic_hooks' => 'file',
            'scheduledefs' => 'from',
            'layoutdefs' => 'from',
            'copy' => 'from',
            'administration' => 'from',
            'dashlets' => 'from',
            'menu' => 'from',
            'relationships' => array('meta_data','module_vardefs','module_layoutdefs'),
        );
        foreach($arr as $key => $pathKeys){
            if (empty($installdefs[$key])) {
                continue;
            }
            if(!is_array($pathKeys)){
                $pathKeys = array($pathKeys);
            }
            foreach($pathKeys as $pathKey) {
                $this->addFilesFromArray($installdefs[$key], $pathKey, $zip);
            }
        }
        //TODO `image_dir`

        print_r($installdefs);
    }

    private function getManifestDetails(){
        $details = array();
        include $this->targetManifest;
        $details['manifest'] = !empty($manifest) ? $manifest : array();
        $details['installdefs'] = !empty($installdefs) ? $installdefs : array();
        $details['upgrade_manifest'] = !empty($upgrade_manifest) ? $upgrade_manifest : array();
        return $details;
    }

    private function preFlight(){
        if(!$this->checkOptions()){
            return false;
        }
        if($this->isHelpOption()){
            $this->showUsage();
            return false;
        }
        if(!$this->checkFiles()){
            return false;
        }
        return true;
    }

	private function isHelpOption(){
		$optKeys = array_keys($this->options);
		if(in_array('h',$optKeys) || in_array('help',$optKeys)){
			return true;
		}
		return false;
	}

	private function checkFiles(){
		if(empty($this->targetManifest) || !is_readable($this->targetManifest)){
			echo "suitecrmpackagebuilder: cannot access manifest file {$this->targetManifest}\n";
            return false;
		}
        if(empty($this->targetZip)){
            echo "suitecrmpackagebuilder: No target zip specified\n";
            return false;
        }
        if(file_exists($this->targetZip)){
            echo "suitecrmpackagebuilder: Target zip file {$this->targetZip} already exists\n";
            return false;
        }
        if(!$this->suitecrmBase){
            echo "suitecrmpackagebuilder: SuiteCRM Base {$this->suitecrmBase} doesn't exist\n";
            return false;
        }

        return true;
	}

	private function checkOptions(){
		$success = true;
		foreach($this->options as $key => $val){
			if(!in_array($key,$this->recognisedOptions)){
				echo "Unrecognised option $key\n";
				$success = false;
				break;
			}
		}
        var_dump($this->options);
		if(!$success){
			echo "Try 'suitecrmpackagebuilder --help' for more information.\n";
		}
        if(!empty($this->options['h']) || !empty($this->options['help'])){
            $this->showUsage();
            return false;
        }
		return $success;
	}

	private function showVersion(){
		?>
		 v0.1
		<?php
	}

	private function showUsage(){
        echo NAME;?>.

Usage:
  suitecrmpackagebuilder [options] <manifestpath> <suitecrmpath> <zippath>
  suitecrmpackagebuilder -h | --help
  suitecrmpackagebuilder --version

Options:
  -h --help     Show this screen.
  --version     Show version.
<?php
	}


}
