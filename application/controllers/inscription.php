<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inscription extends CI_Controller
{
    private $fb;
    
        public function __construct()
        {
            parent::__construct();
            $this->load->model('inscription_model');

            $this->twig->addFunction('validation_errors');
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

	public function index() //Inscription dans le site !!
	{
            $this->form_validation->set_rules('nom', 'Nom complet', 'trim|required');
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[4]|max_length[32]');
            $this->form_validation->set_rules('password2', 'Password Confirmation', 'trim|required|matches[password]');

            if ($this->form_validation->run() == FALSE)
            {
                    $this->twig->render('inscription');
            }
            else
            {        
                $this->inscription_model->insert_inscription();
                $link = base_url('inscription/confirmation').'/'. sha1($this->input->post('email') . 'XkI85BtF');
                
                //send email
                $config = Array(
                    'protocol' => 'smtp',
                    'smtp_host' => 'ssl://smtp.googlemail.com',
                    'smtp_port' => 465,
                    'smtp_user' => 'resa.travelstore@gmail.com',
                    'smtp_pass' => 'travelstore2012',
                    'mailtype'  => 'html'
                    );
                    
                    $this->load->library('email', $config);
                    $this->email->set_newline("\r\n");

                    $this->email->from('bourcedefret@gmail.com', 'SmartShare Team');
                    $this->email->to($this->input->post('email'));

                    $this->email->subject(' Activer Votre compte ');
                    $this->email->message('Pour Activer Votre compte, cliquez sur ce '. anchor($link, 'lien'));
                        
                    if (!$this->email->send())
                        show_error($this->email->print_debugger());
                    else
                        $this->twig->render('formsuccess', array('mail' => true));
            }
	}
        
        public function confirmation($link) //Confirmation du mail
        {
            $resultats = $this->inscription_model->get_all_desabled();
            
            foreach($resultats as $res)
            {
                if(sha1($res->email . 'XkI85BtF') == $link)
                {
                    $this->inscription_model->update_activer($res->id);      
                    redirect('frontend');     
                    break;
                }
            }
        }
        
        public function inscriptionfb() //Formulaire d'inscription avec Facebook
        {
            $this->twig->render('inscription_fb');
        }
        
        public function getResponse() //Traitement des données recu par fb apres inscription avec inscriptionfb()
        {
            if (!$_REQUEST)
            {
                echo '$_REQUEST is empty';
                echo '<br />';
                echo anchor('/', 'Home');
            }
            else
            {
                $response = $this->parse_signed_request($_REQUEST['signed_request'], $this->fb->getAppSecret());
                $this->inscription_model->insert_inscription($response);
                $this->twig->render('formsuccess', array('mail' => false));
            }
        }
        
        public function login() //login depuis le site
        {
            $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|xss_clean');
            $this->form_validation->set_rules('password', 'Mot de passe', 'trim|required|min_length[4]|max_length[255]|xss_clean|callback_check_login');
            
            if ($this->form_validation->run() == FALSE)
                    $this->twig->render('login');
            else
                $this->twig->render('home');
        }
        
        public function check_login() //Fonction de verification de login (callback)
        {
            $req = $this->inscription_model->login();
            
            if(!$req)
            {
                $this->form_validation->set_message('check_login', 'Invalid Email or password');
                return false;
            }
            else
            {
                if (!$req->activer)
                {
                    $this->form_validation->set_message('check_login', 'Utilisateur non Activé!');
                    return false;
                }
                
                //Test de l'ID Facebook
                if($req->facebook_id == 0)
                    $this->associer_facebookID($req->id);
                    
                $login_in = array('id' => $req->id, 'email' => $req->email, 'nom' => $req->nom, 'facebook_id' => $req->facebook_id);
                $this->session->set_userdata('login_in', $login_in);
                return true;
             }
        }
        
        public function associer_facebookID($id) //Association du facebook_id au mail correspondant
        {
            $uid = $this->fb->getUser();
            if (empty($uid))
            {
                $ppp = array();
                $ppp['scope'] = 'email, read_stream, publish_actions';
                $ppp['redirect_uri'] = base_url().'inscription/associer_facebookID/'.$id;
                $ppp['display'] = 'popup';
                redirect($this->fb->getLoginUrl($ppp));
            }
            else
            {
                $this->inscription_model->update_facebookID($id, $uid);
                redirect('inscription/login');
            }
        }
        
        public function loginfb() //Formulaire de connexion du Facebook
        {
            $uid = $this->fb->getUser();
            if (empty($uid))
            {
                $ppp = array();
                $ppp['scope'] = 'email, read_stream, publish_actions';
                $ppp['redirect_uri'] = base_url().'inscription/loginfb/';
                $ppp['display'] = 'popup';
                redirect($this->fb->getLoginUrl($ppp));
            }
            else //User connecté avec facebook
            {
                $res = $this->inscription_model->getUserData($uid);
                $me = $this->fb->api('/me');
                
                if(!$res) //ID inexistant
                {
                    //User inscri sur le site avec formulaire du site et email inscri == email facebook
                    if($this->inscription_model->checkMail($me['email'], $uid))
                    {
                        //facebook ID associé!!
                        redirect('inscription/loginfb');
                    }
                    else //Soit user non inscri ou email inscri != email Facebook
                    {
                        //Cette fonction va verifier l'inscri du user et lui associer le FID si necessaire
                        redirect('inscription/login');
                    }
                }
                else //ID existant
                {
                    $login_in = array('id' => $res->id, 'email' => $res->email, 'nom' => $res->nom, 'facebook_id' => $res->facebook_id);
                    $this->session->set_userdata('login_in', $login_in);
                    redirect('/');
                }
            }
        }
        
        public function logout() //Deconnexion
        {
            $this->fb->destroySession();
            $this->session->unset_userdata('login_in');
            $this->session->sess_destroy();
            redirect('/');
        }
        
        /***************************************************************************************************/
        function parse_signed_request($signed_request, $secret) //Fonction prise de l'API Facebook
        {
            list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

            // decode the data
            $sig = $this->base64_url_decode($encoded_sig);
            $data = json_decode($this->base64_url_decode($payload), true);

            if (strtoupper($data['algorithm']) !== 'HMAC-SHA256')
            {
                error_log('Unknown algorithm. Expected HMAC-SHA256');
                return null;
            }

            // check sig
            $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
            if ($sig !== $expected_sig)
            {
                error_log('Bad Signed JSON signature!');
                return null;
            }
            return $data;
        }
        
        public function base64_url_decode($input) //Fonction prise de l'API Facebook
        {
            return base64_decode(strtr($input, '-_', '+/'));
        }
        /***************************************************************************************************/
}
