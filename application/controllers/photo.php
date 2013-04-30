<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Photo extends CI_Controller
{
    private $fb;
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('photo_model');
        $this->load->model('statut_model');
        
        $this->twig->addFunction('getsessionhelper');
        
        //Facebook Connect
        require_once 'assets/facebook_sdk_src/facebook.php';
        $param = array();
        $param['appId'] = '444753728948897';
        $param['secret'] = '5ab7c77a75fd646619cc98bde08e37e3';
        $param['fileUpload'] = true; // pour envoyer des photos
        $param['cookie'] = false;
        $this->fb = new Facebook($param);
    }
        
    public function index($categorie = "")
    {
        $data = array();
        
        if(empty($categorie))
        {
            $data['res'] = $this->photo_model->getAllPhotos(getsessionhelper()['id']);
            foreach($data['res'] as $value)
            {
                $value->statut = $this->statut_model->getStatut($value->statut)->statut;
                
                if($value->partage == 0)
                    $value->etat = 'Image non partagé';
                else
                    $value->etat = 'Image partagé';
            }
            
            $this->twig->render('photo_view', $data);
        }
        else
        {
            $data['res'] = $this->photo_model->getPhotosByCategorie(getsessionhelper()['id'], $categorie);
            foreach($data['res'] as $value)
            {
                $value->statut = $this->statut_model->getStatut($value->statut)->statut;
                
                if($value->partage == 0)
                    $value->etat = 'Image non partagé';
                else
                    $value->etat = 'Image partagé';
            }
            $this->twig->render('photo_view', $data);
        }
        
    }
    
    public function categorie()
    {
        $this->twig->render('categorie_view');
    }
    
    public function modifier($id)
    {
        if(empty($id))
            exit('Erreur ID Photo');
        
        $data = array();
        $data['s'] = $this->statut_model->getStatut($this->photo_model->getPhoto($id)->statut)->statut;
        $data['l'] = $this->photo_model->getPhoto($id)->localisation;
        
        $this->form_validation->set_rules('statut', '\'Statut\'', 'trim|required|xss_clean');
        
        if($this->form_validation->run())
        {
            $this->photo_model->updatephoto($id);
            redirect('/photo');
        }
        else
        {
            $this->twig->render('modifier_photo_view', $data);
        }
    }

    public function supprimer($id)
    {
        if(empty($id))
            exit('Erreur ID image');
        
        $this->photo_model->deletePhoto($id);
        redirect('/photo');
    }
    
    public function publierfb($id)
    {
        //Initialisation
        $path = 'C:/wamp/www/SmartShare/uploads/';
        $res   = $this->photo_model->getPhoto($id);
        $photo = $path . $res->photo;
        $msg   = $this->statut_model->getStatut($res->statut)->statut;
        
        $uid = $this->fb->getUser();
        
        if (empty($uid)) //User non connecté sur facebook
        {
            $param = array();
            $param['redirect_uri'] = base_url().'photo/publierfb/' . $id;
            $param['display'] = 'popup';
            redirect($this->fb->getLoginUrl($param));
        }
        else //User connecté sur facebook
        {
            try
            {
                $this->fb->api('/me/photos', 'POST', array('source' => '@'.$photo, 'message' => $msg));
                //redirect('/photo');
                if(!$this->photo_model->setShared($id))
                    echo 'Erreur modification attribut partagé de la photo<br />';
                
                redirect('photo/index');
            }
            catch(FacebookApiException $e)
            {
                echo 'Exception:';
                echo '<br />';
                echo $e->getType();
                echo '<br />';
                echo $e->getMessage();
            }   
        }
    }
    
}


/*$img = $this->fb->api('/me/feed', 'POST', array('link' => 'www.lol.com',
                                                            'message' => 'Test'));

$ret_obj = $this->fb->api('/me/photos', 'POST', array('source' => '@' . $photo,
                                                      'message' => $msg,));

echo '<pre>';
print_r($img);
echo '</pre>';
*/
?>


