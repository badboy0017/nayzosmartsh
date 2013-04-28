<?php
class Statut_model extends CI_Model
{
    private $table = 'statut';

    public function __construct()
    {
        parent::__construct();
    }
    
    public function getStatut($id)
    {
        return $this->db->get_where($this->table, array('id' => $id))->row();
    }
    
    public function getAllNonAttachStatus($id)
    {
        return $this->db->where('user', $id)
                 ->where('attache', 0)
                 ->order_by('date_envoi', 'desc')
                 ->get($this->table)
                 ->result();
    }
    
    public function addStatut()
    {
        $data = array('statut' => $this->input->post('statut'),
                      'localisation' => $this->input->post('localisation'),
                      'date_envoi' => date('Y-m-d H:i:s'),
                      'partage' => 0,
                      'attache' => 0,
                      'user' => getsessionhelper()['id']);

        $this->db->insert($this->table, $data);
    }
    
    public function setShared($id)
    {
        if(empty($id))
            return false;
        
        $this->db->update($this->table, array('partage' => 1), array('id' => $id));
        return true;
    }

    public function updateStatut($id)
    {
        if(empty($id))
            return false;
        
        $data = array('localisation' => $this->input->post('localisation'),
                      'statut' => $this->input->post('statut'),
                      'date_envoi' => date('Y-m-d H:i:s'));
        $this->db->update($this->table, $data, array('id' => $id));
        return true;
    }

        public function deleteStatut($id)
    {
        $this->db->delete($this->table, array('id' => $id));
    }

}

?>


