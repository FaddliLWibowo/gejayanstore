<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once('application/controllers/Base.php');
class Admin extends Base {

	//construct
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('M_admin','M_penjual','M_toko','M_produk','M_konfirmasi','M_transaksi'));
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
	//konfirmasi
	function konfirmasi()
	{
		$urisegment = $this->uri->segment(3);
		//pagination setup
		$this->load->library('pagination');
		$config['base_url'] = site_url($this->uri->uri_string());
		$config['per_page'] = 20;
		$uri = $this->uri->segment(4);
		if(empty($uri))$uri=0;
		//end of pagination setup
		switch ($urisegment) {
			case 'menunggu':
				$konfirmasi = $this->M_konfirmasi->konfirmasi('menunggu');
				$count = $konfirmasi->num_rows();
				$view = $this->M_konfirmasi->konfirmasi('menunggu',$config['per_page'],$uri)->result_array();
				$title = 'Konfirmasi Menunggu';
			case 'riwayat':
				$konfirmasi = $this->M_konfirmasi->konfirmasi();
				$count = $konfirmasi->num_rows();
				$view = $this->M_konfirmasi->konfirmasi('',$config['per_page'],$uri)->result_array();
				$title = 'Riwayat Konfirmasi';
		}
		$config['total_rows'] = $count;
		$this->pagination->initialize($config);
		$Data = array(
			'count'=>$count,
			'title'=>$title,
			'link'=>$this->pagination->create_links(),
			'view'=>$view
		);
		$this->baseAdminView('konfirmasi/list',$Data);
	}
	//transaksi
	function transaksi()
	{
		$urisegment = $this->uri->segment(3);
		//pagination setup
		$this->load->library('pagination');
		$config['base_url'] = site_url($this->uri->uri_string());
		$config['per_page'] = 20;
		$uri = $this->uri->segment(4);
		if(empty($uri))$uri=0;
		switch ($urisegment) {
			case 'menunggu':
				$title = 'Transaksi Menunggu';
				$transaksi = $this->M_transaksi->transaksi('menunggu',$config['per_page'],$uri)->result_array();//menampilkan transaksi yahg belum ada status
				$count = $this->M_transaksi->transaksi('menunggu')->num_rows();
				break;
			case 'diproses':
				$title = 'Transaksi Diproses';
				$transaksi = $this->M_transaksi->transaksi('diproses',$config['per_page'],$uri)->result_array();//menampilkan transaksi yang masih diproses
				$count = $this->M_transaksi->transaksi('diproses')->num_rows();
				break;
			case 'lunas':
				$title = 'Transaksi Lunas';
				$transaksi = $this->M_transaksi->transaksi('lunas',$config['per_page'],$uri)->result_array();//menampilkan transaksi yang sudah lunas
				$count = $this->M_transaksi->transaksi('lunas')->num_rows();
			break;
			case 'riwayat':
				$title = 'Riwayat Transaksi';
				$transaksi = $this->M_transaksi->transaksi();
				$transaksi = $this->M_transaksi->transaksi()->result_array();//menampilkan transaksi yang sudah lunas
				$count = $this->M_transaksi->transaksi()->num_rows();
				break;
			case 'detail':
				$urisegment = $this->uri->segment(4);
				return $this->detailtransaksi($urisegment);
			break;
			default:
				redirect(site_url('admin/transaksi/menunggu'));
				break;
		}
		$config['total_rows'] = $count;
		$this->pagination->initialize($config);
		$Data = array(
			'count'=>$count,
			'title'=>$title,
			'view'=>$transaksi,
		);
		$this->baseAdminView('transaksi/list',$Data);
	}
	//detail transaksi
	public function detailtransaksi($idtransaksi)
	{
		echo $idtransaksi;
		$transaksi = $this->M_transaksi->detailtransaksi($idtransaksi);//detail transaksi
		$Data = array(
			'title'=>'Detail Transaksi',
			
			);
		$this->baseAdminView('transaksi/detail',$Data);
	}
	//login failed
	public function loginFailed($note)
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
			'transaksimenunggu'=>$this->M_produk->transaksi('menunggu'),
			'konfirmasimenunggu'=>$this->M_produk->konfirmasi('menunggu'),
			'totaltoko'=>$this->M_toko->countToko($status=""),
		);
		$this->baseAdminView('dashboard',$Data);
	}

	/*MANAJEMEN ONLY*/
	//manajemen penjual

	//menampilkan penjual yang menunggu konfirmasi pembayaran
	public function penjualmenunggu()
	{
		//do search
		if(!empty($_GET['q']))
		{
			redirect(site_url('admin/penjual/search/'.str_replace(' ','-',$_GET['q'])));
		}
		///pagination setup
		$this->load->library('pagination');
		$config['base_url'] = site_url($this->uri->uri_string());//recent url
		$config['per_page'] = 20;
		$Uri = $this->uri->segment(4);
		if(empty($Uri))$Uri=0;//default uri
		$CountPenjual = $this->M_penjual->getSimplePenjual('','','menunggu','')->num_rows();//total rows
		$config['total_rows'] = $CountPenjual;
		$Penjual = $this->M_penjual->getSimplePenjual($config['per_page'],$Uri,'menunggu','')->result_array();//view
		$this->pagination->initialize($config);
		$Data = array
		(
		'title'=>'Penjual Menunggu Konfirmasi',
		'count'=>$CountPenjual,
		'view'=>$Penjual,
		'link'=>$this->pagination->create_links(),
		'script'=>'',
		'action'=>site_url('admin/penjual'),
	);
	$this->baseAdminView('penjual/list',$Data);
}
//tampilan penjual yang telah memiliki toko
public function penjual()
{
	//do search
	if(!empty($_GET['q']))
	{
		redirect(site_url('admin/penjual/search/'.str_replace(' ','-',$_GET['q'])));
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
		case 'menunggu'://menunggu konfirmasi
		$CountPenjual = $this->M_penjual->getPenjual('','','menunggu','')->num_rows();//total rows
		$config['total_rows'] = $CountPenjual;
		$Penjual = $this->M_penjual->getPenjual($config['per_page'],$Uri,'menunggu','')->result_array();//total rows
		$Script = "$('#menunggu').addClass('active');";
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
	'script'=>$Script,
	'action'=>site_url('admin/penjual'),
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
		return redirect($this->agent->referrer());
		break;
		case 'manage'://detail penjual
		$Idpenjual = $this->uri->segment(4);
		$Penjual = $this->M_penjual->detPenjual($Idpenjual);
		//cek status penjual
		$Data = array
		(
		'title'=>$Penjual['namaPemilik'],
		'penjual'=>$Penjual,
		'toko'=>$this->M_toko->detailToko($Penjual['idToko']),
		'promosi'=>$this->M_produk->listProdukToko(100,0,$Penjual['idToko']),
	);
	return $this->baseAdminView('penjual/manage',$Data);
	break;
	case 'managemenunggu'://detail penjual yang sedang menunggu
	$IdPenjual = $this->uri->segment(4);//get id penjual
	$Penjual = $this->M_penjual->detSimplePenjual($IdPenjual);//detail penjual
	$Data = array
	(
	'title'=>$Penjual['namaPemilik'],
	'penjual'=>$Penjual,
	'toko'=>$this->M_toko->tokoByIdPemilik($Penjual['idPemilik'])->row_array(),
	'konfirmasi'=>''
);
return $this->baseAdminView('penjual/managemenunggu',$Data);//detail penjual yang belum melakukan pembayara
break;
default:
# code...
break;
}
}
//manajemen toko
public function toko()
{
	$Data = array
	(
	'title'=>'Manajemen Toko',
);
}
//manajemen promosi
public function promo()
{
	if(empty($this->uri->segment(3))){redirect(site_url('admin/promo/aktif'));}
	//pagination
	$this->load->library('pagination');
	$config['base_url'] = $this->uri->uri_string();
	$config['per_page'] = 20;
	//end of pagination
	$Uri = $this->uri->segment(4);
	if(empty($Uri)){$Uri = 0;}
	switch ($this->uri->segment(3)) {
		case 'aktif':
			$view = $this->M_produk->promoListing($config['per_page'],$Uri,'aktif')->result_array();;
			$count = $this->M_produk->promoListing('','','aktif')->num_rows();
			break;
			case 'banned':
			$view = $this->M_produk->promoListing($config['per_page'],$Uri,'banned')->result_array();;
			$count = $this->M_produk->promoListing('','','aktif')->num_rows();
			break;
			case 'habis':
			$view = $this->M_produk->promoListing($config['per_page'],$Uri,'habis')->result_array();;
			$count = $this->M_produk->promoListing('','','aktif')->num_rows();
			break;
			default:
			# code...
			break;
		}
		$config['total_rows'] = $count;
		$this->pagination->initialize($config);
		$Data = array(
			'title'=>'Daftar Promo '.$this->uri->segment(3),
			'link'=>$this->pagination->create_links(),
			'total'=>$count,
			'view'=>$view
		);
		return $this->baseAdminView('promo/listing',$Data);
	}
	//promo action
	public function actionpromo()
	{
		switch($_GET['act']) {//whats action
			case 'updatestatus':
			echo $status = $_GET['action'];//what the lattest status
			echo $id = $_GET['id'];//whats the item id
			$this->M_produk->changeStatus($id,$status);//update status
			redirect($this->agent->referrer());////ke referrer page
			break;
		}
	}
	//search promosi
	public function caripromo()
	{
		if(!empty($_GET['q'])){redirect(site_url('admin/caripromo/'.str_replace(' ','-',$_GET['q'])));}//redirect for SEO friendly
		//pagination
		$this->load->library('pagination');
		$config['base_url'] = $this->uri->uri_string();
		$config['per_page'] = 20;
		//end of pagination
		$Uri = $this->uri->segment(4);
		if(empty($uri)){$Uri = 0;}
		$keyword = str_replace('-',' ',$this->uri->segment(3));
		$count = $this->M_produk->promoSearch('','',$keyword)->num_rows();
		$view = $this->M_produk->promoSearch($config['per_page'],$Uri,$keyword)->result_array();
		$config['total_rows'] = $count;
		$this->pagination->initialize($config);
		$Data = array(
			'title'=>'Pencarian Promo : '.$this->uri->segment(3),
			'link'=>$this->pagination->create_links(),
			'total'=>$count,
			'view'=>$view
		);
		return $this->baseAdminView('promo/listing',$Data);
	}
	//search penjual
	public function caripenjual()
	{
		if(!empty($_GET['q'])){redirect(site_url('admin/caripenjual/'.str_replace(' ','-',$_GET['q'])));}//redirect for SEO friendly
		//pagination
		$this->load->library('pagination');
		$config['base_url'] = $this->uri->uri_string();
		$config['per_page'] = 20;
		//end of pagination
		$Uri = $this->uri->segment(4);
		if(empty($uri)){$Uri = 0;}
		$keyword = str_replace('-',' ',$this->uri->segment(3));
		$count= $this->M_penjual->getPenjual('','','',$keyword)->num_rows();//total rows
		$config['total_rows'] = $count;
		$view = $this->M_penjual->getPenjual($config['per_page'],$Uri,'',$keyword)->result_array();//total rows
		$config['total_rows'] = $count;
		$this->pagination->initialize($config);
		$Data = array(
			'title'=>'Pencarian Penjual : '.$this->uri->segment(3),
			'link'=>$this->pagination->create_links(),
			'count'=>$count,
			'view'=>$view
		);
		return $this->baseAdminView('penjual/list',$Data);
	}
	//manajemen kategori
	public function kategori()
	{
		if(!empty($_GET['act']))
		{
			switch ($_GET['act']) {
				case 'addmainkat':
					$this->db->insert('kategoriItem',array('namaKategori'=>$_POST['nama'],'deskripsiKategori'=>$_POST['deskripsi']));
					redirect($this->agent->referrer());
					break;
				case 'addsubkat':
					$this->db->insert('SubKategoriItem',array('idKategoriItem'=>$_POST['mainkat'],'namaSubKategori'=>$_POST['subkat']));
					redirect($this->agent->referrer());
					break;
				case 'delmainkat';
					$this->db->where('idKategoriItem',$_GET['id']);
					$this->db->delete('kategoriItem');
					redirect($this->agent->referrer());
					break;
				case 'delsubkat';
					$this->db->where('idSubKategori',$_GET['id']);
					$this->db->delete('SubKategoriItem');
					redirect($this->agent->referrer());
					break;
				default:
					redirect($this->agent->referrer());
					break;
			}
		}else//just view
		{
		$segment = $this->uri->segment(3);
		$idmainkat = $this->uri->segment(4);
		switch($segment){
			case 'barang'://manajemen kategori barang
				$Data = array(
					'title'=>'Manajemen Kategori',
					'mainkat'=>$this->M_produk->getKategori('barang',''),//get all item kategori
					'subkat'=>$this->M_produk->getSubKat($idmainkat),//get sub kat
					'script'=>'$scope.semua = "active";',
					);
				return $this->baseAdminView('kategori/barang',$Data);
				break;
			case 'toko'://menajemen kategori toks
				echo 'on construct';
				break;	
			default://not defined uri segment 3
			redirect(site_url('kategori/barang'));
			break;
		}
		}
	}
	/*END OF MANAJEMEN*/

	//logout
	public function logout()
	{
		$this->session->sess_destroy();
		redirect(site_url('admin'));
	}

}
