<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Profil extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('inscription_model', 'inscriManager');
        $this->twig->addFunction('getsessionhelper');
    }
        
    public function index()
    {
        $this->twig->render('profil_view');
    }
    
    public function modifier_profil()
    {
        $this->form_validation->set_rules('nom', '\'Nom\'', 'trim|required|max_length[30]|xss_clean');
        $this->form_validation->set_rules('email', '\'Email\'', 'trim|required|valid_email|xss_clean');
        
        if($this->form_validation->run())
        {
            if(!$this->inscriManager->updateProfil(getsessionhelper()['id']))
                echo 'Probleme ID. Fonction: models/inscription_model/updateProfil';
            else
            {
                $temp = $this->session->userdata('login_in');
                $temp['nom'] = $this->input->post('nom');
                $temp['email'] = $this->input->post('email');
                $this->session->set_userdata('login_in', $temp);
                redirect('/profil');
            }
        }
        else
        {
            $this->twig->render('modifier_profil_view');
        }
    }
    
    public function modifier_password()
    {
        $this->form_validation->set_rules('old', '\'Ancien mot de passe\'', 'trim|required|min_length[4]|max_length[255]|alpha_dash|encode_php_tags|xss_clean|callback_checkOldPass');
        $this->form_validation->set_rules('new', '\'Nouveau mot de passe\'', 'trim|required|min_length[4]|max_length[255]|alpha_dash|encode_php_tags|xss_clean');
        $this->form_validation->set_rules('renew', '\'Nouveau mot de passe\'', 'trim|required|min_length[4]|max_length[255]|alpha_dash|encode_php_tags|xss_clean|matches[new]');
        
        if($this->form_validation->run())
        {
            if(!$this->inscriManager->setPassword(getsessionhelper()['id']))
                echo 'Probleme ID. Fonction: models/inscription_model/setPassword';
            else
                redirect('/profil');
        }
        else
        {
            $this->twig->render('modifier_password_view');
        }
    }
    
    public function checkOldPass()
    {
        if($this->inscriManager->getPassword(getsessionhelper()['id']) === sha1($this->input->post('old')))
            return true;
        else
        {
            $this->form_validation->set_message('checkOldPass', 'Verifier votre ancien mot de passe');
            return false;
        }
    }
    
}

?>


