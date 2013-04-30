<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Statut extends CI_Controller
{
    private $fb;
    
    public function __construct()
    {
        parent::__construct();
        $this->twig->addFunction('getsessionhelper');
        $this->load->model('statut_model');
        
        //Facebook Connect
        require_once 'assets/facebook_sdk_src/facebook.php';
        $param = array();
        $param['appId'] = '444753728948897';
        $param['secret'] = '5ab7c77a75fd646619cc98bde08e37e3';
        $param['fileUpload'] = true; // pour envoyer des photos
        $param['cookie'] = false;
        $this->fb = new Facebook($param);
    }
        
    public function index()
    {
        $data['res'] = $this->statut_model->getAllNonAttachStatus(getsessionhelper()['id']);
        foreach($data['res'] as $value)
        {
            if($value->partage == 0)
                $value->etat = 'Statut non partagé';
            else
                $value->etat = 'Statut partagé';
        }
        $this->twig->render('statut_view', $data);
    }
    
    public function nouveau()
    {
        $this->form_validation->set_rules('localisation', '\'Localisation\'', 'trim|max_length[255]|xss_clean');
        $this->form_validation->set_rules('statut', '\'Statut\'', 'trim|required|xss_clean');
        
        if($this->form_validation->run())
        {
            $this->statut_model->addStatut();
            redirect('/statut');
        }
        else
        {
            $this->twig->render('nouveau_statut_view');
        }
    }

    public function modifier($id)
    {
        if(empty($id))
            exit('Erreur ID Statut');
        
        $data = array();
        $data['s']  = $this->statut_model->getStatut($id)->statut;
        $data['id'] = $id;
        
        $this->form_validation->set_rules('statut', '\'Statut\'', 'trim|required|xss_clean');
        
        if($this->form_validation->run())
        {
            echo $id . '<br />';
            echo $this->input->post('statut');
            return;
            $this->statut_model->updateStatut($id);
            redirect('/statut');
        }
        else
        {
            $this->twig->render('modifier_statut_view', $data);
        }
    }


    public function supprimer($id)
    {
        if(empty($id))
            exit('Erreur ID Statut');
        
        $this->statut_model->deleteStatut($id);
        redirect('/statut');
    }
    
    public function publierfb($id)
    {
        //Initialisation
        $msg   = $this->statut_model->getStatut($id)->statut;
        
        $uid = $this->fb->getUser();
        
        if (empty($uid)) //User non connecté sur facebook
        {
            $param = array();
            $param['redirect_uri'] = base_url().'statut/publierfb/' . $id;
            $param['display'] = 'popup';
            redirect($this->fb->getLoginUrl($param));
        }
        else //User connecté sur facebook
        {
            try
            {
                $this->fb->api('/me/feed', 'POST', array('message' => $msg));
                
                if(!$this->statut_model->setShared($id))
                    echo 'Erreur modification attribut partagé du statut <br />';
                    
                redirect('statut');
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
    
    public function search()
    {
        
        $data['res'] = $this->statut_model->getAllNonAttachStatus(getsessionhelper()['id']);
        
        
        
        
        $data = array();
        $text = $this->input->post('rech');
        $res  = $this->statut_model->search($text);
        $data['res'] = $res;
        
        if(!$res)
            echo 'aucun resultat';
        else
        {
            foreach($data['res'] as $value)
            {
                if($value->partage == 0)
                    $value->etat = 'Statut non partagé';
                else
                    $value->etat = 'Statut partagé';
            }
            $this->twig->render('search_view', $data);
        }
    }
    
}


