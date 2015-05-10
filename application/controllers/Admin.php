<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/controllers/Base.php');
class Admin extends Base {

	//construct
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('M_admin','M_penjual'));
		//is admin lgged in
		$session = $this->adminLoggedIn();
		if($session==FALSE && $this->uri->uri_string() != 'admin'){redirect(site_url('admin'));}
	}
	public function index()
	{
		$session = $this->adminLoggedIn();
		if($session==TRUE){redirect(site_url('admin/dashboard'));}
		//login process
		if(!empty($_POST['inputusername']) && !empty($_POST['inputpassword']))
		{
			//cek into database
			$username=$this->input->post('inputusername');
			$password=$this->input->post('inputpassword');
			$CanLogin = $this->M_admin->canLogin($username,md5($password));
			if($CanLogin == 1){//user and password match
				//create session
				$adminData = $this->M_admin->adminData($username);
				$userdata = array(
					'idadmin'=>$adminData['idAdmin'],
					'email'=>$adminData['email'],
					'password'=>$adminData['password'],
					'telp'=>$adminData['telp'],
					'alamat'=>$adminData['alamat'],
				);
				$sessionData['adminLoggedIn'] = $userdata;
				$this->session->set_userdata($sessionData);
				redirect(site_url('admin'));
			}else{//user and password not match
				$this->loginFailed('Username dan password tidak cocok');
			}
		} else{//login form
			//end login process
			$Data = array(
				'title'=>'Admin Login',
			);
			$this->baseAdminView('login',$Data);
		}
	}
	//login failed
	public function loadingFailed($note)
	{
		$Data = array(
			'title'=>'Admin Login',
			'error'=>$note
		);
		$this->baseAdminView('login',$Data);
	}
	//dashboard
	public function dashboard()
	{
		$Data  = array(
			'title'=>'Admin Dashboard',
		);
		$this->baseAdminView('dashboard',$Data);
	}

	/*MANAJEMEN ONLY*/
	//manajemen penjual
	public function penjual()
	{
		//do search
		if(!empty($_GET['q']))
		{
			redirect(site_url('admin/penjual/'.str_replace(' ','-',$_GET['q'])));
		}
		///pagination setup
		$this->load->library('pagination');
		$config['base_url'] = site_url($this->uri->uri_string());//recent url
		$config['per_page'] = 20;
		$Uri = $this->uri->segment(4);
		if(empty($Uri))$Uri=0;//default uri
		switch ($this->uri->segment(3)) {
			case 'search'://search penjual
			$CountPenjual = $this->M_penjual->getPenjual('','','',str_replace('-',' ',$this->uri->segment(3)))->num_rows();//total rows
			$config['total_rows'] = $CountPenjual;
			$Penjual = $this->M_penjual->getPenjual($config['per_page'],$Uri,'',str_replace('-',' ',$this->uri->segment(3)))->result_array();//view
			$Script = "$('#semua').addClass('active');";
			break;
			case 'active'://search penjual
			$CountPenjual = $this->M_penjual->getPenjual('','','active','')->num_rows();//total rows
			$config['total_rows'] = $CountPenjual;
			$Penjual = $this->M_penjual->getPenjual($config['per_page'],$Uri,'active','')->result_array();//total rows
			$Script = "$('#active').addClass('active');";
			break;
			case 'banned'://search penjual
			$CountPenjual = $this->M_penjual->getPenjual('','','banned','')->num_rows();//total rows
			$config['total_rows'] = $CountPenjual;
			$Penjual = $this->M_penjual->getPenjual($config['per_page'],$Uri,'banned','')->result_array();//total rows
			$Script = "$('#banned').addClass('active');";
			break;
			case 'all'://search penjual
			$CountPenjual = $this->M_penjual->getPenjual('','','','')->num_rows();//total rows
			$config['total_rows'] = $CountPenjual;
			$Penjual = $this->M_penjual->getPenjual($config['per_page'],$Uri,'','')->result_array();//total rows
			$Script = "$('#semua').addClass('active');";
			break;
			default:
			redirect(site_url('admin/penjual/all'));
			break;
		}
		$this->pagination->initialize($config);
		$Data = array
		(
		'title'=>'Olah Data Penjual',
		'count'=>$CountPenjual,
		'view'=>$Penjual,
		'link'=>$this->pagination->create_links(),
		'script'=>$Script
	);
	$this->baseAdminView('penjual/list',$Data);
}
//action penjual
public function actionpenjual()
{
	$action = $this->uri->segment(3);
	switch ($action){
		case 'ubahstatus'://ubah status active atau banned
			$idpenjual = $this->uri->segment(4);
			$penjual = $this->M_penjual->detPenjual($idpenjual);
			if($penjual['status']=='active'){$status='banned';}
			else{$status='active';}
			$this->db->where('idPemilik',$penjual['idPemilik']);
			$data = array('status'=>$status);
			$this->db->update('pemilikToko',$data);
			break;

		default:
			# code...
			break;
	}
	return redirect($this->agent->referrer());
}
//manajemen toko
public function toko()
{
	$Data = array
	(
	'title'=>'Manajemen Toko',
);
}
/*END OF MANAJEMEN*/

//logout
public function logout()
{
	$this->session->sess_destroy();
	redirect(site_url('admin'));
}
}