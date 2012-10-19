<?php  defined('C5_EXECUTE') or die(_("Access Denied."));

class DoctrinePackage extends Package {

    protected $pkgHandle = 'doctrine';
    protected $appVersionRequired = '5.5.1';
    protected $pkgVersion = '1.0.0';


    public function getPackageDescription() {
        return t('Doctrine 2.3 integration to concrete5 package system');
    }

    public function getPackageName(){
        return t('Doctrine 2.3');
    }

	public function onStart()
	{
		 $uh = Loader::helper('concrete/urls');
	     
	}
	
	 public function upgrade(){
        parent::upgrade();
       
    }

  
    public function install() {
        $pkg = parent::install();
		Loader::model('collection_types');
        Loader::model('single_page');
		
		// install dashboard views
		$p1 = SinglePage::add('/dashboard/doctrine',$pkg);
		$p1->update(array('cDescription'=>$this->getPackageDescription()));
		
    }

    

}

    ?>