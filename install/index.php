<?php
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory as IoDirectory;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadLanguageFile(__FILE__);
class partnercode_solutioncode extends CModule {
	const partnerCode = 'partnercode';
	const solutionCode	= 'solutioncode';
	const moduleClass = 'PartnerCode\SolutionCode';
	const moduleClassEvents = self::moduleClass . '\Events';
	const moduleClassCache =  self::moduleClass . '\Cache';
	
	var $MODULE_ID = self::partnerCode . '.' . self::solutionCode;
	var $MODULE_LANG_CODE = self::partnerCode . '.' . self::solutionCode;
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = 'Y';
	var $exclusionAdminFiles;
	
	function __construct() {
		$arModuleVersion = array();
		
		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path . '/version.php');
		
		$this->exclusionAdminFiles=array(
			'..',
			'.',
			'menu.php',
			'operation_description.php',
			'task_description.php'
		);
		
		$this->MODULE_LANG_CODE = ToUpper(str_replace('.' , '_', $this->MODULE_ID));
		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage($this->MODULE_LANG_CODE . '_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage($this->MODULE_LANG_CODE . '_MODULE_DESCRIPTION');
		
		$this->PARTNER_NAME = Loc::getMessage(ToUpper(self::partnerCode) . '_PARTNER_NAME');
		$this->PARTNER_URI = Loc::getMessage(ToUpper(self::partnerCode) . '_PARTNER_URI');
		
		$this->MODULE_SORT = 1;
	}
	
	// Определяем место размещения модуля
	public function GetPath($notDocumentRoot = false) {
		if($notDocumentRoot) {
			return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
		} else {
			return dirname(__DIR__);
		}
	}
	
	// Проверяем что система поддерживает D7
	public function isVersionD7() {
		return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
	}
	
	function InstallDB() {
		ModuleManager::registerModule($this->MODULE_ID);
		
		return true;
	}
	
	function UnInstallDB() {
		ModuleManager::unRegisterModule($this->MODULE_ID);
		
		return true;
	}
	
	function InstallEvents() {
		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible('main', 'OnBeforeProlog', $this->MODULE_ID, self::moduleClassEvents, 'OnBeforePrologHandler');
		
		spl_autoload_register();
		
		return true;
	}
	
	function UnInstallEvents() {
		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID, self::moduleClassEvents, 'OnBeforePrologHandler');
		
		return true;
	}
	
	function InstallFiles() {
		if (IoDirectory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
			if ($dir = opendir($path)) {
				while (false !== $item = readdir($dir)) {
					if (in_array($item,$this->exclusionAdminFiles))
						continue;
					
					$sIncludeAdminPage = '<' . '? require($_SERVER["DOCUMENT_ROOT"]."' . $this->GetPath(true) . '/admin/' . $item . '");?' . '>';
					file_put_contents(Application::getDocumentRoot() . '/bitrix/admin/' . $this->MODULE_ID . '_' . $item, $sIncludeAdminPage);
				}
				
				closedir($dir);
			}
		}
		if(IoDirectory::isDirectoryExists($path = $this->GetPath() . '/install/css')) {
			CopyDirFiles($path, Application::getDocumentRoot() . '/bitrix/css/' . $this->MODULE_ID, true, true);
		}
		if(IoDirectory::isDirectoryExists($path = $this->GetPath() . '/install/js')) {
			CopyDirFiles($path, Application::getDocumentRoot() . '/bitrix/js/' . $this->MODULE_ID, true, true);
		}
		if(IoDirectory::isDirectoryExists($path = $this->GetPath() . '/install/images')) {
			CopyDirFiles($path, Application::getDocumentRoot() . '/bitrix/images/' . $this->MODULE_ID, true, true);
		}
		if(IoDirectory::isDirectoryExists($path = $this->GetPath() . '/install/components')) {
			CopyDirFiles($path, Application::getDocumentRoot() . '/bitrix/components', true, true);
		}
		if(IoDirectory::isDirectoryExists($path = $this->GetPath() . '/install/wizards')) {
			CopyDirFiles($path, Application::getDocumentRoot() . '/bitrix/wizards', true, true);
		}
		
		$this->InstallGadget();
		
		if(preg_match('/.bitrixlabs.ru/', $_SERVER["HTTP_HOST"])) {
			@set_time_limit(0);
			
			require_once(Application::getDocumentRoot() . "/bitrix/modules/fileman/include.php");
			CFileMan::DeleteEx(array('s1', '/bitrix/modules/' . $this->MODULE_ID . '/install/wizards'));
			CFileMan::DeleteEx(array('s1', '/bitrix/modules/' . $this->MODULE_ID . '/install/gadgets'));
		}
		
		return true;
	}
	
	function UnInstallFiles() {
		if (IoDirectory::isDirectoryExists($path = $this->GetPath() . '/admin')) {
			if ($dir = opendir($path)) {
				while (false !== $item = readdir($dir)) {
					if (in_array($item, $this->exclusionAdminFiles))
						continue;
					
					Bitrix\Main\IO\File::deleteFile(Application::getDocumentRoot() . '/bitrix/admin/' . $this->MODULE_ID . '_' . $item);
				}
				
				closedir($dir);
			}
		}
		DeleteDirFilesEx('/bitrix/css/' . $this->MODULE_ID . '/');
		DeleteDirFilesEx('/bitrix/js/' . $this->MODULE_ID . '/');
		DeleteDirFilesEx('/bitrix/images/' . $this->MODULE_ID . '/');
		DeleteDirFilesEx('/bitrix/wizards/' . self::partnerCode . '/' . self::solutionCode . '/');
		
		$this->UnInstallGadget();
		
		return true;
	}
	
	function InstallGadget() {
		return true;
	}
	
	function UnInstallGadget() {
		return true;
	}
	
	function DoInstall() {
		global $APPLICATION;
		
		if($this->isVersionD7()) {
			$this->InstallDB();
			$this->InstallFiles();
			$this->InstallEvents();
		} else {
			$APPLICATION->ThrowException(Loc::getMessage($this->MODULE_LANG_CODE . '_INSTALL_ERROR_VERSION'));
		}
		
		$APPLICATION->IncludeAdminFile(Loc::getMessage($this->MODULE_LANG_CODE . '_INSTALL_TITLE', array('#MODULE_NAME_SHORT#' => Loc::getMessage($this->MODULE_LANG_CODE . '_MODULE_NAME_SHORT'))), $this->GetPath() . '/install/step.php');
	}
	
	function DoUninstall() {
		global $APPLICATION;
		
		$this->UnInstallFiles();
		$this->UnInstallEvents();
		$this->UnInstallDB();
		
		$APPLICATION->IncludeAdminFile(Loc::getMessage($this->MODULE_LANG_CODE . '_UNINSTALL_TITLE', array('#MODULE_NAME_SHORT#' => Loc::getMessage($this->MODULE_LANG_CODE . '_MODULE_NAME_SHORT'))), $this->GetPath() . '/install/unstep.php');
	}
	
	function GetModuleRightList() {
		return array(
			'reference_id' => array('D', 'K', 'S', 'W'),
			'reference' => array(
				'[D] ' . Loc::getMessage($this->MODULE_LANG_CODE . '_DENIED'),
				'[K] ' . Loc::getMessage($this->MODULE_LANG_CODE . '_READ_COMPONENT'),
				'[S] ' . Loc::getMessage($this->MODULE_LANG_CODE . '_WRITE_SETTINGS'),
				'[W] ' . Loc::getMessage($this->MODULE_LANG_CODE . '_FULL'))
		);
	}
}