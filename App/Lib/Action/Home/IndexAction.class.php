<?php

class IndexAction extends BaseAction
{

    const  PAGE_PER_NUM = 10; //每页记录数
    public $uid;
    public $type;
    private $u_name ;
    /**
     *@初始化方法
     *@判断openId是否为空
     */
    function _initialize(){
        import("ORG.SensitiveFilter"); //载入敏感词过滤
        if ($_POST) {
            foreach ($_POST as $k=>$v){
                $_POST[$k] = SensitiveFilter::filter(htmlspecialchars($v)) === false ? '含有非法词汇,你不能评价！' : $v ;
                foreach ($v as $k1=>$v1){
                    $_POST[$k][$k1] = SensitiveFilter::filter(htmlspecialchars($v1)) === false ? '含有非法词汇,你不能评价！' : $v1 ;
                }
            }
        }
        if ($_GET) {
            foreach ($_GET as $k=>$v){
                $_GET[$k] = SensitiveFilter::filter(htmlspecialchars($v)) === false ? '含有非法词汇,你不能评价！' : $v ;
                foreach ($v as $k1=>$v1){
                    $_GET[$k][$k1] = SensitiveFilter::filter(htmlspecialchars($v1)) === false ? '含有非法词汇,你不能评价！' : $v1 ;
                }
            }
        }
        //获取cookie里边的信息
        $cookieUser = $this->getUser();
        $user =  M('MUser'); //用户表
        $Mc = M('MCredit');
        $Me =  M('Meeting');
        $ms =  M('MStudent');
        $mt =  M('MTeacher');
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd'],'u_type'=>$cookieUser['type']))->find()['u_id'];
        $this->uid = $uid;
        $this->type = $cookieUser['type'];
        $this->u_name =  $this->get_name($cookieUser['user']);
        //获取学生学分
        $McInfo =  $Mc->where(array('c_uid'=>$uid))->find();
        //获得补登会议学分
        $hyxf =  $Me->field('sum(m_hyxf) as xf')->where(array('m_uid'=>$uid,'m_mtype'=>1))->find();
        if (empty($McInfo)) {
            $data['c_uid'] = $uid;
            $data['c_no'] = $cookieUser['user'];
            $data['c_user'] =  $this->u_name;
            $data['c_number'] = 0;
            $data['c_time'] = time();
            $Mc->add($data);
        }

        $this->assign('xf', $hyxf['xf'] + $McInfo['c_number']);
        $this->assign('auth',cookie('auth'));
        $this->assign('mcAll',  $McInfo);
        $this->assign('u_type_init', $cookieUser['type']);    //会议和学分：校外没有会议和学分，教师没有学分和补登
    }

    //得到学生老师信息
    private function get_name ($cookie){
        $ms =  M('MStudent');
        $mt =  M('MTeacher');
        $msName =  $ms->where(array('s_no'=>$cookie))->find()['s_name'];
        $mt_Name =  $mt->where(array('t_no'=>$cookie))->find()['t_name'];
        if (!empty($msName)) {
            $_name = $msName;
        }
        if (!empty($mt_Name)) {
            $_name = $mt_Name;
        }
        return $_name;
    }


    //会议内容
    public function index(){
       $Mt = M('Meeting');
       $Ms = M('MSignup');
       $M = M('MMt');  //会议类型
       $Mf = M('MFormat');
       $Mr = M('MRoom');
       $time_1 = date('Y-m-d',time()+60*60*24*15);
       //近期会议
       $MtAll = $Mt->where(array('m_mtype'=>array('neq', 1),'m_status'=>array('in','0,1'),'m_a_status'=>1,'m_time'=>array('elt',$time_1)))->order('time DESC')->select();
        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_baoming'] = $Ms->where(array('s_mid'=>$v['m_id']))->count();
            $MtAll[$k]['m_address'] =  $Mr->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
            $count = $Ms->where(array('s_uid'=>$this->uid,'s_mid'=>$v['m_id']))->count();
            $MtAll[$k]['m_baoflag'] =  $count ==1 ? 1: 2 ;
            $oneSign = $Ms->where(array('s_mid'=>$v['m_id'],'s_uid'=>$this->uid))->find();   //查询当前用户只能参加才能评论
            if (empty($oneSign)) {
                $flag = '1';
            }else {
                $flag = '2';
            }
            $MtAll[$k]['m_flag'] = $flag;
            $exi = strtotime($v['m_time'].' '.$v['m_start']);
            if ( time() >= $exi) {
                $Mt->where(array('m_id' => $v['m_id']))->save(array('m_status' => 1));
            }
            $exi2 = strtotime($v['m_time'].' '.$v['m_end']);
            if ( time() >= $exi2) {
                $Mt->where(array('m_id' => $v['m_id']))->save(array('m_status' => 2));
            }
        }

        //历史的会议
        $Mt__All = $Mt->where(array('m_mtype'=>array('neq', 1),'m_status'=>2,'m_a_status'=>1))->order('time DESC')->select();
        foreach ($Mt__All as $k=>$v){
            $Mt__All[$k]['m_baoming'] = $Ms->where(array('s_mid'=>$v['m_id']))->count();
            $Mt__All[$k]['m_address'] =  $Mr->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
            $oneSign = $Ms->where(array('s_mid'=>$v['m_id'],'s_uid'=>$this->uid))->find();   //查询当前用户只能参加才能评论
            if (empty($oneSign)) {
                $flag = '1';
            }else {
                $flag = '2';
            }
            $Mt__All[$k]['m_flag'] = $flag;
        }

        //海报图
        $fShow =  $Mf->field('f_show,f_show1')->where(array('f_confirm'=>1))->find();
       $this->assign('fshow', $fShow);
       $this->assign('mtAll', $M->select());
       $this->assign('MtAll',$MtAll);
       $this->assign('Mt__All',$Mt__All);
       $this->display('Details/index');
    }


    //全部会议
    public function index_all(){
        $Mt =  M('Meeting');
        $Ms =  M('MSignup');
        $Mf = M('MFormat');
        $Mr = M('MRoom');
        $map['m_status'] = array('in','0,1,2');
        $map['m_a_status'] = 1;
        $map['m_mtype'] = array('neq',1);
       // print_r($_GET);
        if ($_GET['type'] == 1) {
            //会议时间排序
            if($_GET['start_time']){
                $map['m_time'][] = array('egt', trim($_GET['start_time']));
            }

            if($_GET['end_time']){
                $_end_time = trim($_GET['end_time']);
                $map['m_time'][] = array('elt', $_end_time);
            }
        }
        if ($_GET['type'] == 2){
            //会议发布时间排序
            if($_GET['start_time']){
                $map['time'][] = array('egt', strtotime(trim($_GET['start_time'])));
            }

            if($_GET['end_time']){
                $_end_time = trim($_GET['end_time']);
                $map['time'][] = array('elt', strtotime($_end_time)+(24*60*60-1));
            }
        }
        //会议类型
        if($_GET['m_type']){
            $map['m_type'] = $_GET['m_type'];
        }


        $count = $Mt->where($map)->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mt->where($map)->order('time DESC')->limit($page->firstRow , $page->listRows)->select();
        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_baoming'] = $Ms->where(array('s_mid'=>$v['m_id']))->count();
            $MtAll[$k]['m_address'] =  $Mr->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
            $count = $Ms->where(array('s_uid'=>$this->uid,'s_mid'=>$v['m_id']))->count();
            $MtAll[$k]['m_baoflag'] =  $count ==1 ? 1: 2 ;
            $oneSign = $Ms->where(array('s_mid'=>$v['m_id'],'s_uid'=>$this->uid))->find();   //查询当前用户只能参加才能评论
            if (empty($oneSign)) {
                $flag = '1';
            }else {
                $flag = '2';
            }
            $MtAll[$k]['m_flag'] = $flag;


            $exi = strtotime($v['m_time'].' '.$v['m_start']);
            if ( time() >= $exi) {
                $Mt->where(array('m_id' => $v['m_id']))->save(array('m_status' => 1));
            }
            $exi2 = strtotime($v['m_time'].' '.$v['m_end']);
            if ( time() >= $exi2) {
                $Mt->where(array('m_id' => $v['m_id']))->save(array('m_status' => 2));
            }

        }
        //海报图
        $fShow =  $Mf->field('f_show,f_show1')->where(array('f_confirm'=>1))->find();
        $M = M('MMt');  //会议类型
        $mtAll = $M->select();
        $this->assign('fshow', $fShow);
        $this->assign('mtAll', $mtAll);
        $this->assign("page", $showPage);
        $this->assign('MtAll',$MtAll);
        $this->display('Details/index_all');
    }



    //近期会议
    public function index_jinqi(){
        $Mt = M('Meeting');
        $Ms = M('MSignup');
        $M = M('MMt');  //会议类型
        $Mf = M('MFormat');
        $Mr = M('MRoom');
        $time_1 = date('Y-m-d',time()+60*60*24*15);
        //近期会议
        $count = $Mt->where(array('m_mtype'=>array('neq', 1),'m_status'=>array('in','0,1'),'m_a_status'=>1,'m_time'=>array('elt',$time_1)))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mt->where(array('m_mtype'=>array('neq', 1),'m_status'=>array('in','0,1'),'m_a_status'=>1,'m_time'=>array('elt',$time_1)))->limit($page->firstRow , $page->listRows)->order('time DESC')->select();
        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_baoming'] = $Ms->where(array('s_mid'=>$v['m_id']))->count();
            $MtAll[$k]['m_address'] =  $Mr->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
            $count = $Ms->where(array('s_uid'=>$this->uid,'s_mid'=>$v['m_id']))->count();
            $MtAll[$k]['m_baoflag'] =  $count ==1 ? 1: 2 ;
            $oneSign = $Ms->where(array('s_mid'=>$v['m_id'],'s_uid'=>$this->uid))->find();   //查询当前用户只能参加才能评论
            if (empty($oneSign)) {
                $flag = '1';
            }else {
                $flag = '2';
            }
            $MtAll[$k]['m_flag'] = $flag;
            $exi = strtotime($v['m_time'].' '.$v['m_start']);
            if ( time() >= $exi) {
                $Mt->where(array('m_id' => $v['m_id']))->save(array('m_status' => 1));
            }
            $exi2 = strtotime($v['m_time'].' '.$v['m_end']);
            if ( time() >= $exi2) {
                $Mt->where(array('m_id' => $v['m_id']))->save(array('m_status' => 2));
            }
        }

        //海报图
        $fShow =  $Mf->field('f_show,f_show1')->where(array('f_confirm'=>1))->find();
        $this->assign('fshow', $fShow);
        $this->assign("page", $showPage);
        $this->assign('mtAll', $M->select());
        $this->assign('MtAll',$MtAll);
        $this->display('Details/index_jinqi');
    }




    //历史会议
    public function index_lishi(){
        $Mt = M('Meeting');
        $Ms = M('MSignup');
        $M = M('MMt');  //会议类型
        $Mf = M('MFormat');
        $Mr = M('MRoom');

        //历史的会议
        $count = $Mt->where(array('m_mtype'=>array('neq', 1),'m_status'=>2,'m_a_status'=>1))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $Mt__All = $Mt->where(array('m_mtype'=>array('neq', 1),'m_status'=>2,'m_a_status'=>1))->limit($page->firstRow , $page->listRows)->order('time DESC')->select();
        foreach ($Mt__All as $k=>$v){
            $Mt__All[$k]['m_baoming'] = $Ms->where(array('s_mid'=>$v['m_id']))->count();
            $Mt__All[$k]['m_address'] =  $Mr->where(array('room_name'=>$v['m_rname']))->find()['room_address'];

            $oneSign = $Ms->where(array('s_mid'=>$v['m_id'],'s_uid'=>$this->uid))->find();   //查询当前用户只能参加才能评论
            if (empty($oneSign)) {
                $flag = '1';
            }else {
                $flag = '2';
            }
            $Mt__All[$k]['m_flag'] = $flag;
        }

        //海报图
        $fShow =  $Mf->field('f_show,f_show1')->where(array('f_confirm'=>1))->find();
        $this->assign('fshow', $fShow);
        $this->assign('mtAll', $M->select());
        $this->assign("page", $showPage);
        $this->assign('Mt__All',$Mt__All);
        $this->display('Details/index_lishi');
    }


    //解密cookie--auth
    private function getUser (){
        $deUser =   json_decode(cookie('auth'));
        $user =  decrypt_de($deUser->u,c('MD5_KEY'));
        $pwd =  decrypt_de($deUser->p,c('MD5_KEY'));
        $type =  decrypt_de($deUser->t,c('MD5_KEY'));
        return array('user'=>$user,'pwd'=>$pwd,'type'=>$type);
    }

    //会议心得提交
    public function xinde (){
        $D = D("MExperience");
        $user =  M('MUser');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] !=1) {
            $uid = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }else {
            $uid = $user->where(array('u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }
        if (IS_AJAX) {
            try {
                $_POST['e_content'] = htmlspecialchars($_POST['e_content']);
                $_POST['e_time'] = time();
                $_POST['e_uid'] = $uid;
                if($data = $D->create(I('post.'))){

                    $last = $D->where(array('e_uid'=>$uid,'e_mid'=>$_POST['e_mid']))->order('e_time DESC')->find();
                    $_time = time() -$last['e_time'];
                    if ($_time < 1200) {
                        die('-4');
                    }

                    $re = $D->add($data);
                    if($re){
                        die('-1');
                    }else{
                        die('-2');
                    }
                }else{
                    die('-2');
                }
            } catch (Exception $e) {
                die('-2');
            }

        }
    }




    //立即参会
    public function canjia (){
        $Ms =  M('MSignup');
        $user =  M('MUser');
        $Me =  M('Meeting');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] !=1) {
            $uid = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_id'];
            $u_name = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_name'];
        }else {
            $uid = $user->where(array('u_pwd' => $cookieUser['pwd']))->find()['u_id'];
            $u_name = $user->where(array('u_pwd' => $cookieUser['pwd']))->find()['u_name'];
        }
        if (IS_AJAX) {
            try {
                if (I('post.baoming') == I('post.number')) {
                    $Me->where(array('m_id'=>I('post.s_mid')))->save(array('m_n_exist'=>I('post.baoming')));
                    die('-3');
                }
                $_POST['s_no'] = $u_name;
                $_POST['s_name'] = $this->u_name;
                $_POST['s_time'] = time();
                $_POST['s_uid'] = $uid;
                $_POST['s_mtype'] = $_POST['m_mtype'];
                $_POST['s_meet_name'] = $Me->where(array('m_id'=>$_POST['s_mid']))->find()['m_name'];
                if($data = $Ms->create(I('post.'))){
                    $_ms = $Ms->where(array('s_uid'=>$uid,'s_mid'=>$_POST['s_mid']))->find();
                    if (!empty($_ms)) {
                        die('-2');
                    }
                    $re = $Ms->add($data);
                    if($re){
                        die('-1');
                    }
                }else{
                    die('-2');
                }
            } catch (Exception $e) {
                die('-2');
            }

        }
    }


    //详情页
    public function willsign(){
        if (empty(cookie('auth'))) die('1');
        $D = D("MEvaluate");//评论
        $ME = D("MExperience");
        $user = M('MUser');
        $Mp = M('MPoster');
        $Ms = M('MSignup');
        $Mf = M('MFormat');
        $Mt = M('Meeting');
        $Mg = M('MGrade');
        $Mc = M('MCredit');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] !=1) {
            $uid = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }else {
            $uid = $user->where(array('u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }
        if (IS_AJAX) {
            try {
                $_POST['e_content'] = htmlspecialchars($_POST['e_content']);
                $_POST['e_time'] = time();
                $_POST['e_uid'] = $uid;
                if($data = $D->create(I('post.'))){
                    $last = $D->where(array('e_uid'=>$uid,'e_mid'=>$_POST['e_mid']))->order('e_time DESC')->find();
                    $_time = time() -$last['e_time'];
                    if ($_time < 300) {
                        die('-4');
                    }
                    $re = $D->add($data);
                    if($re){

                        //参加并且评论一条的得到学分
                        $oneMe =  $D->where(array('e_uid'=>$uid,'e_mid'=>$_POST['e_mid']))->find();
                        $oneMeCount =  $D->where(array('e_uid'=>$uid,'e_mid'=>$_POST['e_mid']))->count();
                        $oneMs = $Ms->where(array('s_uid'=>$uid,'s_mid'=>$_POST['e_mid']))->find();
                        $oneMsCount = $Ms->where(array('s_uid'=>$uid,'s_mid'=>$_POST['e_mid']))->count();
                        $number = $Mc->where(array('c_uid'=>$uid))->find()['c_number'];
                        if (!empty($oneMe) && !empty($oneMs) && $oneMsCount == 1 && $oneMeCount == 1) {
                            $grade = $Mt->field('m_grade')->where(array('m_id'=>$_POST['e_mid']))->find()['m_grade'];
                            $xuefen = $Mg->field('g_xuefen')->where(array('g_id'=>$grade))->find()['g_xuefen'];
                            $data['c_number'] = $number + $xuefen;
                            $Mc->where(array('c_uid'=>$uid))->save($data);

                        }


                        die('-1');
                    }else{
                        die('-2');
                    }
                }else{
                    die('-2');
                }
            } catch (Exception $e) {
                die('-2');
            }

        }
        $id = I('get.id');
        if ($id >0) {
            $MtAll = $Mt->where(array('m_id'=>$id,'m_status'=>array('in','0,1,2')))->find();
            if ($MtAll['m_a_status'] == 2) $this->error('会议正在审核中！');
            if (empty($MtAll) && $MtAll['m_a_status'] == 1) $this->error('会议已经结束不能查看！');
            $MtAll['m_type_name'] = M('MMt')->where(array('mt_id'=>$MtAll['m_type']))->find()['mt_name'];
            $MtAll['m_grade_name'] = M('MGrade')->where(array('g_id'=>$MtAll['m_grade']))->find()['g_name'];
            $MtAll['m_xuefen'] = $Mg->where(array('g_id'=>$MtAll['m_grade']))->find()['g_xuefen'];
            $MMaLL = D("MMechanism")->where(array('m_id'=>$MtAll['m_jigou']))->find()['m_name'];
            $MtAll['m_jigou'] = $MMaLL;
            $MtAll['p_images'] = $Mp->where(array('p_mid'=>$MtAll['m_id']))->find()['p_images'];
            $MtAll['m_baoming'] = $Ms->where(array('s_mid'=>$MtAll['m_id']))->count();
            /*会议评价*/
            $count = $D->getList(I('get.'), 0, 0,1,$id);
            import("ORG.Util.Page"); //载入分页类
            $page = new Page($count,self::PAGE_PER_NUM);
            $showPage = $page->show();
            $list = $D->getList(I('get.'),$page->firstRow, $page->listRows,0,$id);
            $ms =  M('MStudent');
            $mt =  M('MTeacher');
            foreach ($list as $k=>$v){
                $u_name = $user->where(array('u_id'=>$v['e_uid']))->find()['u_name'];
                $msName =  $ms->where(array('s_no'=>$u_name))->find()['s_name'];
                $mt_Name =  $mt->where(array('t_no'=>$u_name))->find()['t_name'];
                if (!empty($msName)) {
                    $_name1 = $msName;
                }
                if (!empty($mt_Name)) {
                    $_name1 = $mt_Name;
                }
                $list[$k]['u_name'] =  $_name1 ;

            }
            $this->assign("MEvaluatePage", $showPage);
            $this->assign("list", $list);
            $this->assign("MEvaluatecount", $count);
            /*会议评价*/
            /*会议心得*/
            $MEcount = $ME->getList(I('get.'), 0, 0,1,$id);
            $MEpage = new Page($MEcount,self::PAGE_PER_NUM);
            $MEshowPage = $MEpage->show();
            $MElist = $ME->getList(I('get.'),$MEpage->firstRow, $MEpage->listRows,0,$id);
            foreach ($MElist as $k=>$v){
                $MElist[$k]['u_name'] = $user->where(array('u_id'=>$v['e_uid']))->find()['u_name']; ;
            }
            //海报图
            $fShow =  $Mf->field('f_show,f_show1,f_show2')->where(array('f_confirm'=>1))->find();
            $this->assign("MExperiencePage", $MEshowPage);
            $this->assign("MElist", $MElist);
            $this->assign("MExperiencecount", $MEcount);
            /*会议心得*/
            //查询当前用户只能参加才能评论
            $oneSign = $Ms->where(array('s_mid'=>$MtAll['m_id'],'s_uid'=>$uid))->find();

            //print_r($MtAll);exit;
            $this->assign('auth',cookie('auth'));
            $this->assign('MtAll',$MtAll);
            $this->assign('id',$id);
            $this->assign('fshow',$fShow);
            $this->assign('user',$this->u_name);
            $this->assign('oneSign',$oneSign);
            $this->display('Details/willsign');
        }
    }




    //会议室选择
    public function shentable(){
        if (empty(cookie('auth'))) die('1');
        //列出会议室
        $Mr =  M('MRoom');
        $Me = M('Meeting');
        $MM = M("MMechanism"); /*所属机构*/
        $MD = M("MDepartmentInfo");/*院系*/
        $map['room_type'] = 1;
        //会议发布时间排序
        if($_GET['start_date']){
            $map['room_time'][] = array('egt', strtotime(trim($_GET['start_date'])));
        }

        if($_GET['end_date']){
            $_end_time = trim($_GET['end_date']);
            $map['room_time'][] = array('elt', strtotime($_end_time)+(24*60*60-1));
        }

        //会议类型
        if($_GET['room_number']){
            if ($_GET['room_number'] == 1) {
                $map['room_number'] = array('between', '1,5');
            }else if ($_GET['room_number'] == 2){
                $map['room_number'] = array('between', '5,10');
            }else if ($_GET['room_number'] == 3){
                $map['room_number'] = array('between', '10,20');
            }else if ($_GET['room_number'] == 4){
                $map['room_number'] = array('between', '20,50');
            }else if ($_GET['room_number'] == 5){
                $map['room_number'] = array('egt', '50');
            }
        }
        $MrInfo =  $Mr->where($map)->select();
        foreach ($MrInfo as $k=>$v) {
            $MMaLL = $MM->where(array('m_id'=>$v['room_jigou']))->find()['m_name'];
            $MDaLL = $MD->where(array('d_id'=>$v['room_yx']))->find()['d_name'];
            $MrInfo[$k]['room_jigou'] = $MMaLL;
            $MrInfo[$k]['room_yx'] = $MDaLL;
            $MrInfo[$k]['meeting_jinxing'] = $Me->field('m_id,m_name,m_start,m_end,m_time,m_rname')->where(array('m_rname'=>$v['room_name']))->order('m_time ASC')->select();
            foreach ($MrInfo[$k]['meeting_jinxing'] as $k1=>$v2) {
                $MrInfo[$k]['meeting_jinxing'][$k1] = $v2;
            }
        }


        $cur = $this->getmonsun();
        $ThisDate = array();
        $new = array();
        $newDate = array();
        for($i = strtotime(date('Y-m-d',$cur['mon'])); $i <= strtotime(date('Y-m-d',$cur['sun'])); $i += 86400) {
            $ThisDate[date("Y-m-d",$i)]=date("Y-m-d",$i);
        }

        foreach ($ThisDate as $k=>$v){
            foreach ($MrInfo as $k1=>$v1) {
                $MrInfo[$k1]['meeting_jinxing2'][$k] = array();
                foreach ($MrInfo[$k1]['meeting_jinxing'] as $_k=>$_v){
                   if ( $k === $_v['m_time']) {
                       $new[] = $_v;
                       //print_r($new);
                       $MrInfo[$k1]['meeting_jinxing2'][$k] = $MrInfo[$k1]['meeting_jinxing'];
                   }
                }
            }
        }


        foreach ($MrInfo as $k11=>$v11) {
            foreach ($MrInfo[$k11]['meeting_jinxing2'] as $k1 => $v1) {
                foreach ($v1 as $_k1 => $_v1) {
                    if ($k1 != $_v1['m_time']){
                        unset($MrInfo[$k11]['meeting_jinxing2'][$k1][$_k1]);
                    }
                }
            }
        }

        //print_r($MrInfo);exit;

        $dateAll = array(1,2,3,4,5,6,7);
        $this->assign('dateAll', $dateAll);
        $this->assign('mon', $cur['mon']);
        $this->assign('sun', $cur['sun']);
        $this->assign('MrAll', $MrInfo);
        $this->display('Details/shentable');
    }



    //获取周一到周日
    private function getmonsun($start = 0,$end = 14){
        //$start = 0 7 14 21
        //$end =14 7 0 -7 -14
        $curtime=time();
        $curweekday = date('w');
        $curweekday = $curweekday?$curweekday:7;
        $curmon = $curtime - (($curweekday-1)+$start)*86400;
        $cursun = $curtime + (($end-7) - $curweekday)*86400;
        $cur['mon'] = $curmon;
        $cur['sun'] = $cursun;
        return $cur;
    }






    //我发起的会议
    public function mefaqi(){
        if (empty(cookie('auth'))) die('1');
        $user =  M('MUser');
        $M = M('MMt');
        $Mg = M('MGrade');
        $Mt =  M('Meeting');
        $cookieUser = $this->getUser();
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
        /*获取用户信息*/
        $count = $Mt->where(array('m_mtype'=>array('neq', 1),'m_uid'=>$uid))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mt->where(array('m_mtype'=>array('neq', 1),'m_uid'=>$uid))->order('m_time DESC')->limit($page->firstRow , $page->listRows)->select();
        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
            $MtAll[$k]['m_address'] = M('MRoom')->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
        }
        $this->assign('MtAll',$MtAll);
        $this->assign("page", $showPage);
        $this->display('Details/mefaqi');
    }


    //确认会议室
    public function confirm(){
        if (IS_AJAX) {
            $time =json_decode($_POST['time']);
            $date2 = $_POST['date2'];
            $m_start = $date2 .' ' .$_POST['m_start'];
            $m_end = $date2 . ' ' .$_POST['m_end'];
            $start =  strtotime($m_start);
            $end =  strtotime($m_end);
            $roomid = $_POST['room_id'];
            foreach ($time as $k=>$v){
                $v->m_start = strtotime($date2 . ' ' . $v->m_start);
                $v->m_end = strtotime($date2 . ' ' . $v->m_end);
                if ($start >= $v->m_start && $end <= $v->m_end ) {
                    die('-2');
                }
            }
            M('MRoom')->where(array('room_id'=>$roomid))->save(array('room_expire'=>time()));
            //print_r($time);exit;
            die('-1');
        }
    }



    //已有会议室
    public function roomdetails(){
        if (empty(cookie('auth'))) $this->error('请登录！');
        $id =  I('get.id');
        $date = I('get.date');
        if ($id > 0) {
            $user = M('MUser');//获取用户信息
            $D = M("Meeting");
            $M = M('MMt');
            $Mg = M('MGrade');
            $Mu = M('MUser');
            $Mr = M('MRoom');
            $Msh = M("MShape");
            $MM = M("MMechanism"); //所属机构
            $cookieUser = $this->getUser();
            if ($cookieUser['type'] == 1) $this->error('您不能查看已有会议！');
            $uid = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_id'];
            $list =  $Mr->where(array('room_id'=>$id))->limit('0,1')->find();
            if (empty($list)) $this->error('错误！');
            $MMaLL = $MM->where(array('m_id' => $list['room_jigou']))->find()['m_name'];
            $list['room_jigou'] = $MMaLL;
            $list['room_shape'] = $Msh->where(array('s_id' => $list['room_shape']))->find()['s_name'];
            $list['meeting'] = $D->where(array('m_rname' => $list['room_name'],'m_time'=>$date))->select();
            $list['time'] = $D->field('m_start,m_end')->where(array('m_rname' => $list['room_name'],'m_time'=>$date))->select();
            $list['time'] = json_encode($list['time']);
            //过期时间清0
            if (!empty($list['room_expire']) && $list['room_expire'] > 0) {
                $ex =  time() - $list['room_expire'];
                if ($ex >= 1200) {
                    M('MRoom')->where(array('room_id' => $id))->save(array('room_expire' => 0));
                }else if ($ex <= 1200) {
                    //$this->error('你好，有别人正在添加会议，请不要添加');
                }
            }
            //print_r($list);exit;
            $this->assign("list", $list);
            $this->assign('date', $date);
            $this->display('Details/roomdetails');
        }else {
            $this->error('错误！');
        }
    }


    //自筹会议室列表
    public function zichou_list(){
        if (empty(cookie('auth'))) die('1');
        $user =  M('MUser');
        $M = M('MMt');
        $Mg = M('MGrade');
        $Mt =  M('Meeting');
        $cookieUser = $this->getUser();
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
        /*获取用户信息*/
        $count = $Mt->where(array('m_a_status'=>1,'m_uid'=>$uid,'m_mtype'=>2))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mt->where(array('m_a_status'=>1,'m_uid'=>$uid,'m_mtype'=>2))->order('m_time DESC')->limit($page->firstRow , $page->listRows)->select();

        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
        }
        $this->assign('MtAll',$MtAll);
        $this->assign("page", $showPage);
        $this->display('Details/zichou_list');
    }


    //会议室列表
    public function zichoulist(){
        if (empty(cookie('auth'))) $this->error('请登录！');
        /*获取用户信息*/
        $user =  M('MUser');
        $MM = M("MMechanism");  /*所属机构*/
        $MD = M("MDepartmentInfo"); /*院系*/
        $Mr =  M('MRoom');
        $Msh = M("MShape");
        $cookieUser = $this->getUser();
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
        $count = $Mr->where(array('room_type'=>1))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mr->where(array('room_type'=>1))->order('room_time DESC')->limit($page->firstRow , $page->listRows)->select();

        foreach ($MtAll as $k=>$v){
            $MMaLL = $MM->where(array('m_id'=>$v['room_jigou']))->find()['m_name'];
            $MDaLL = $MD->where(array('d_id'=>$v['room_yx']))->find()['d_name'];
            $Mshall = $Msh->where(array('s_id'=>$v['room_shape']))->find()['s_name'];
            $MtAll[$k]['room_jigou'] = $MMaLL;
            $MtAll[$k]['room_yx'] = $MDaLL;
            $MtAll[$k]['room_shape'] = $Mshall;
        }
        $this->assign('MtAll',$MtAll);
        $this->assign("page", $showPage);
        $this->display('Details/zichoulist');
    }

    //查看收件箱信息
    public function messages(){
        if (!empty(cookie('auth'))) {
            $cookieUser = $this->getUser();
            if ($cookieUser['type'] == 1) $this->error('您不能查看消息列表！');
            //获取邮件
            $Me = M('MEmail');
            $oneMe =  $Me->where(array('e_id'=>I('get.id')))->find();
            $Me->where(array('e_id'=>I('get.id')))->save(array('e_type'=>1));
            if (empty($oneMe)) $this->error('错误！');
            $ms =  M('MStudent');
            $mt =  M('MTeacher');
            $mu =  M('MUser');
            $msName =  $ms->where(array('s_no'=>$oneMe['e_uid']))->find()['s_name'];
            $mtName =  $mt->where(array('t_no'=>$oneMe['e_uid']))->find()['t_name'];
            $muName =  $mu->where(array('u_id'=>$oneMe['e_uid']))->find()['u_name'];
            $ms_Name =  $ms->where(array('s_no'=>$oneMe['e_sid']))->find()['s_name'];
            $mt_Name =  $mt->where(array('t_no'=>$oneMe['e_sid']))->find()['t_name'];
            $mu_Name =  $mu->where(array('u_id'=>$oneMe['e_sid']))->find()['u_name'];
            if (!empty($msName)) {
                $_name = $msName;
            }
            if (!empty($mtName)) {
                $_name = $mtName;
            }
            if (!empty($muName)) {
                $_name = $muName;
            }

            if (!empty($ms_Name)) {
                $name = $ms_Name;
            }
            if (!empty($mt_Name)) {
                $name = $mt_Name;
            }
            if (!empty($mu_Name)) {
                $name = $mu_Name;
            }
            $oneMe['e_uusername'] = $_name; //发件人
            $oneMe['e_susername'] = $name; //收件人
            $this->assign('oneMe', $oneMe);
            $this->display('Details/messages');
        }
    }



    //删除电子邮件
    public function remove (){
        $Me = M('MEmail');
        if (IS_AJAX) {
            $Me->delete(I('post.id'));
            die('1');
        }
    }
    //发送电子邮件
    public function sendEmail (){
        if (IS_POST) {
            $Me = M('MEmail');
            $cookieUser = $this->getUser();
            try {
                //print_r($_FILES);exit;
                if (empty ($_POST['e_sid'])) {
                    $this->error('收件人不能为空！');
                }
                if (empty ($_POST['e_zhuti'])) {
                    $this->error('主题不能为空！');
                }
                if (empty ($_POST['e_zhengwen'])) {
                    $this->error('正文不能为空！');
                }


                $file_name = $this->upload($_FILES['e_fujian'],'fujian','',1);
                $_POST['e_fujian'] = $file_name;
                $_POST['e_time'] = time();

                if($data = $Me->create(I('post.'))){
                    $strpos =  strpos($data['e_sid'],',');
                    if ($strpos > 0) {
                        $_arr_str = explode(',',$data['e_sid']);
                        foreach ($_arr_str as $v){
                                $data['e_sid'] = $v;
                                $time = time();
                                $Me->query("insert into cumtb_m_email(e_sid,e_uid,e_zhuti,e_zhengwen,e_fujian,e_time) VALUES ('" . $v . "',  '" . $_POST['e_uid'] . "' ,  '" . $_POST['e_zhuti'] . "', '" . $_POST['e_zhengwen'] . "','" . $_POST['e_fujian'] . "','" . $time . "' )");
                                $re = true;
                        }

                    }else {
                            $re = $Me->add($data);

                    }
                    //echo $Me->getLastSql();exit;
                    if($re){
                        //$this->success("发送成功",U("/Index/emails"));
                        echo "<script type='text/javascript'>location.href='/Index/emails.html'</script>";
                        return ;
                    }else{
                        throw new Exception('添加失败', 1);
                    }
                }else{
                    throw new Exception($Me->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }else {
            $this->error('错误！');
        }
    }

    public function emails_shou (){
        if (!empty(cookie('auth'))) {
            $cookieUser = $this->getUser();
            if ($cookieUser['type'] == 1) $this->error('您不能查看消息列表！');
            $teacher =  D('MTeacher'); //教师表
            $student =  D('MStudent'); //学生表
            import("ORG.Util.Page"); //载入分页类
            $user =  M('MUser');
            $cookieUser = $this->getUser();
            $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
            //获取发件
            $Me = M('MEmail');
            $countM =  $Me->where(array('e_uid'=>$cookieUser['user']))->count();
            $pageM = new Page($countM,self::PAGE_PER_NUM);
            $showPageM = $pageM->show();
            $listM =   $Me->where(array('e_uid'=>$cookieUser['user']))->limit($pageM->firstRow, $pageM->listRows)->select();
            foreach($listM as $k=>$v){
                $ms =  M('MStudent');
                $mt =  M('MTeacher');
                $msName =  $ms->where(array('s_no'=>$v['e_sid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_sid']))->find()['t_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                $listM[$k]['username'] = $_name;
            }
            //获取收件
            $countM_s =  $Me->where(array('e_sid'=>$cookieUser['user']))->count();
            $pageM_s = new Page($countM_s,self::PAGE_PER_NUM);
            $showPageM_s = $pageM_s->show();
            $listM_s =   $Me->where(array('e_sid'=>$cookieUser['user']))->limit($pageM_s->firstRow, $pageM_s->listRows)->select();
            foreach($listM_s as $k=>$v){

                $ms =  M('MStudent');
                $mt =  M('MTeacher');
                $mu =  M('MUser');
                $msName =  $ms->where(array('s_no'=>$v['e_uid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_uid']))->find()['t_name'];
                $muName =  $mu->where(array('u_id'=>$v['e_uid']))->find()['u_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                if (!empty($muName)) {
                    $_name = $muName;
                }
                $listM_s[$k]['username'] = $_name;
            }

            /*院系*/
            $MD = M("MDepartmentInfo");
            $MDaLL = $MD->select();
            //专业
            $Msp = M("MStudentProfe");
            $MspaLL = $Msp->select();
            //学生
            if ($cookieUser['type'] == 3) {
                $teacher_name =  $ms->field('s_teacher')->where(array('s_no'=>$cookieUser['user']))->find()['s_teacher'];
                $t_no =  $mt->field('t_no')->where(array('t_name'=>$teacher_name))->find()['t_no'];
                $this->assign('t_no',$t_no);
            }
            //教师
            if ($cookieUser['type'] == 2) {
                $t_name =  $mt->field('t_name')->where(array('t_no'=>$cookieUser['user']))->find()['t_name'];
                $s_no =  $ms->field('s_no,s_name')->where(array('s_teacher'=>$t_name))->select();
                $s_no_name = array();
                foreach ($s_no as $k=>$v){
                    $s_no[$k] = $v['s_no'];
                    $s_no_name[$k] = $v['s_name'];
                }
                $s_no = implode(',',$s_no);
                $s_no_name = implode(',',$s_no_name);
                $this->assign('s_no',$s_no);
                $this->assign('s_no_name',$s_no_name);
            }
            $this->assign('MDaLL', $MDaLL);
            $this->assign('MspaLL', $MspaLL);
            $this->assign("pageM_s", $showPageM_s);
            $this->assign("listM_s", $listM_s);
            $this->assign("pageM", $showPageM);
            $this->assign("listM", $listM);
            $this->assign("uid", $uid);
            $this->assign("userid", $cookieUser['user']);
            $this->assign('auth',cookie('auth'));
            $this->display('Details/emails_shou');
        }else {
            die('1');
        }



    }

    public function emails_fa (){
        if (!empty(cookie('auth'))) {
            $cookieUser = $this->getUser();
            if ($cookieUser['type'] == 1) $this->error('您不能查看消息列表！');
            $teacher =  D('MTeacher'); //教师表
            $student =  D('MStudent'); //学生表
            $ms =  M('MStudent');
            $mt =  M('MTeacher');
            $mu =  M('MUser');
            $MD = M("MDepartmentInfo");      /*院系*/
            $Msp = M("MStudentProfe");     //专业
            $user =  M('MUser');     /*获取用户信息*/
            $Me = M('MEmail');
            $cookieUser = $this->getUser();
            $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
            //获取发件
            import("ORG.Util.Page"); //载入分页类
            $countM =  $Me->where(array('e_uid'=>$cookieUser['user']))->count();
            $pageM = new Page($countM,self::PAGE_PER_NUM);
            $showPageM = $pageM->show();
            $listM =   $Me->where(array('e_uid'=>$cookieUser['user']))->limit($pageM->firstRow, $pageM->listRows)->select();
            foreach($listM as $k=>$v){
                $msName =  $ms->where(array('s_no'=>$v['e_sid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_sid']))->find()['t_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                $listM[$k]['username'] = $_name;
            }
            //获取收件
            $countM_s =  $Me->where(array('e_sid'=>$cookieUser['user']))->count();
            $pageM_s = new Page($countM_s,self::PAGE_PER_NUM);
            $showPageM_s = $pageM_s->show();
            $listM_s =   $Me->where(array('e_sid'=>$cookieUser['user']))->limit($pageM_s->firstRow, $pageM_s->listRows)->select();
            foreach($listM_s as $k=>$v){

                $msName =  $ms->where(array('s_no'=>$v['e_uid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_uid']))->find()['t_name'];
                $muName =  $mu->where(array('u_id'=>$v['e_uid']))->find()['u_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                if (!empty($muName)) {
                    $_name = $muName;
                }
                $listM_s[$k]['username'] = $_name;
            }

            $MDaLL = $MD->select();
            $MspaLL = $Msp->select();


            //学生
            if ($cookieUser['type'] == 3) {
                $teacher_name =  $ms->field('s_teacher')->where(array('s_no'=>$cookieUser['user']))->find()['s_teacher'];
                $t_no =  $mt->field('t_no')->where(array('t_name'=>$teacher_name))->find()['t_no'];
                $this->assign('t_no',$t_no);
            }
            //教师
            if ($cookieUser['type'] == 2) {
                $t_name =  $mt->field('t_name')->where(array('t_no'=>$cookieUser['user']))->find()['t_name'];
                $s_no =  $ms->field('s_no,s_name')->where(array('s_teacher'=>$t_name))->select();
                $s_no_name = array();
                foreach ($s_no as $k=>$v){
                    $s_no[$k] = $v['s_no'];
                    $s_no_name[$k] = $v['s_name'];
                }
                $s_no = implode(',',$s_no);
                $s_no_name = implode(',',$s_no_name);
                $this->assign('s_no',$s_no);
                $this->assign('s_no_name',$s_no_name);
            }


            $this->assign('MDaLL', $MDaLL);
            $this->assign('MspaLL', $MspaLL);
            $this->assign("pageM_s", $showPageM_s);
            $this->assign("listM_s", $listM_s);
            $this->assign("pageM", $showPageM);
            $this->assign("listM", $listM);
            $this->assign("uid", $uid);
            $this->assign("userid", $cookieUser['user']);
            $this->assign('auth',cookie('auth'));
            $this->display('Details/emails_fa');
        }else {
            //$this->error('请登录！');
            die('1');
        }

    }


    //电子邮件
    public function emails(){
        if (!empty(cookie('auth'))) {
            $cookieUser = $this->getUser();
            if ($cookieUser['type'] == 1) $this->error('您不能查看消息列表！');
            $teacher =  D('MTeacher'); //教师表
            $student =  D('MStudent'); //学生表
            $ms =  M('MStudent');
            $mt =  M('MTeacher');
            $mu =  M('MUser');
            $Me = M('MEmail');
            $user =  M('MUser');   /*获取用户信息*/
            $MD = M("MDepartmentInfo");    /*院系*/
            $Msp = M("MStudentProfe");         //专业
            $cookieUser = $this->getUser();
            $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
            /*获取用户信息*/
            //获取发件
            import("ORG.Util.Page"); //载入分页类

            $countM =  $Me->where(array('e_uid'=>$cookieUser['user']))->count();
            $pageM = new Page($countM,self::PAGE_PER_NUM);
            $showPageM = $pageM->show();
            $listM =   $Me->where(array('e_uid'=>$cookieUser['user']))->limit($pageM->firstRow, $pageM->listRows)->select();
            foreach($listM as $k=>$v){
                $msName =  $ms->where(array('s_no'=>$v['e_sid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_sid']))->find()['t_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                $listM[$k]['username'] = $_name;
            }
            //获取收件
            $countM_s =  $Me->where(array('e_sid'=>$cookieUser['user']))->count();
            $pageM_s = new Page($countM_s,self::PAGE_PER_NUM);
            $showPageM_s = $pageM_s->show();
            $listM_s =   $Me->where(array('e_sid'=>$cookieUser['user']))->limit($pageM_s->firstRow, $pageM_s->listRows)->select();
            foreach($listM_s as $k=>$v){
                $msName =  $ms->where(array('s_no'=>$v['e_uid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_uid']))->find()['t_name'];
                $muName =  $mu->where(array('u_id'=>$v['e_uid']))->find()['u_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                if (!empty($muName)) {
                    $_name = $muName;
                }
                $listM_s[$k]['username'] = $_name;
            }
            $MDaLL = $MD->select();
            $MspaLL = $Msp->select();

            //学生
            if ($cookieUser['type'] == 3) {
                $teacher_name =  $ms->field('s_teacher')->where(array('s_no'=>$cookieUser['user']))->find()['s_teacher'];
                $t_no =  $mt->field('t_no')->where(array('t_name'=>$teacher_name))->find()['t_no'];
                $this->assign('t_no',$t_no);
            }
            //教师
            if ($cookieUser['type'] == 2) {
                $t_name =  $mt->field('t_name')->where(array('t_no'=>$cookieUser['user']))->find()['t_name'];
                $s_no =  $ms->field('s_no,s_name')->where(array('s_teacher'=>$t_name))->select();
                $s_no_name = array();
                foreach ($s_no as $k=>$v){
                    $s_no[$k] = $v['s_no'];
                    $s_no_name[$k] = $v['s_name'];
                }
                $s_no = implode(',',$s_no);
                $s_no_name = implode(',',$s_no_name);
                $this->assign('s_no',$s_no);
                $this->assign('s_no_name',$s_no_name);
            }



            $this->assign('MDaLL', $MDaLL);
            $this->assign('MspaLL', $MspaLL);
            $this->assign("pageM_s", $showPageM_s);
            $this->assign("listM_s", $listM_s);
            $this->assign("pageM", $showPageM);
            $this->assign("listM", $listM);
            $this->assign("uid", $uid);
            $this->assign("userid", $cookieUser['user']);
            $this->assign('auth',cookie('auth'));
            $this->display('Details/emails');
        }else {
           die('1');
        }
    }

    public function emails1 (){
        if (!empty(cookie('auth'))) {
            $cookieUser = $this->getUser();
            if ($cookieUser['type'] == 1) $this->error('您不能查看消息列表！');
            $teacher =  D('MTeacher'); //教师表
            $student =  D('MStudent'); //学生表
            $ms =  M('MStudent');
            $mt =  M('MTeacher');
            $mu =  M('MUser');
            //获取发件
            $Me = M('MEmail');
            /*获取用户信息*/
            $user =  M('MUser');
            import("ORG.Util.Page"); //载入分页类
            //教师
            if ($_GET['user'] == 2) {
                //教师列表
                $countT = $teacher->getList(I('get.'), 0, 0,1);
                $pageT = new Page($countT,10000);
                $showPageT = $pageT->show();
                $listT = $teacher->getList(I('get.'),$pageT->firstRow, $pageT->listRows,0);
                //print_r($listT);
                if (isset($_GET['daochu'])) {
                    $title = array(
                        't_name'=> array(
                            'title'=>'姓名'
                        ),
                        't_sex'=> array(
                            'title'=>'性别'
                        ),
                        't_no'=> array(
                            'title'=>'编号'
                        ),
                        't_department'=> array(
                            'title'=>'院系'
                        ),
                        't_phone'=> array(
                            'title'=>'电话号码'
                        )
                    );
                    $this->commonExportCvs($listT,$title);
                }

            }
            if ($_GET['user'] == 3) {
                //学生列表
                $countS = $student->getList(I('get.'), 0, 0, 1);
                $pageS = new Page($countS, 10000);
                $showPageS = $pageS->show();
                $listS = $student->getList(I('get.'), $pageS->firstRow, $pageS->listRows, 0);

                if (isset($_GET['daochu'])) {
                    $title = array(
                        's_name'=> array(
                            'title'=>'姓名'
                        ),
                        's_sex'=> array(
                            'title'=>'性别'
                        ),
                        's_no'=> array(
                            'title'=>'编号'
                        ),
                        's_department'=> array(
                            'title'=>'院系'
                        ),
                        's_phone'=> array(
                            'title'=>'电话号码'
                        )
                    );
                    $this->commonExportCvs($listS,$title);
                }
               // print_r($listS);exit;
            }

            $cookieUser = $this->getUser();
            $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
            /*获取用户信息*/

            $countM =  $Me->where(array('e_uid'=>$cookieUser['user']))->count();
            $pageM = new Page($countM,self::PAGE_PER_NUM);
            $showPageM = $pageM->show();
            $listM =   $Me->where(array('e_uid'=>$cookieUser['user']))->order('e_time desc')->limit($pageM->firstRow, $pageM->listRows)->select();
            foreach($listM as $k=>$v){

                $msName =  $ms->where(array('s_no'=>$v['e_sid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_sid']))->find()['t_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                $listM[$k]['username'] = $_name;
            }
            //获取收件
            $countM_s =  $Me->where(array('e_sid'=>$cookieUser['user']))->count();
            $pageM_s = new Page($countM_s,self::PAGE_PER_NUM);
            $showPageM_s = $pageM_s->show();
            $listM_s =   $Me->where(array('e_sid'=>$cookieUser['user']))->limit($pageM_s->firstRow, $pageM_s->listRows)->select();
            foreach($listM_s as $k=>$v){

                $msName =  $ms->where(array('s_no'=>$v['e_uid']))->find()['s_name'];
                $mtName =  $mt->where(array('t_no'=>$v['e_uid']))->find()['t_name'];
                $muName =  $mu->where(array('u_id'=>$v['e_uid']))->find()['u_name'];
                if (!empty($msName)) {
                    $_name = $msName;
                }
                if (!empty($mtName)) {
                    $_name = $mtName;
                }
                if (!empty($muName)) {
                    $_name = $muName;
                }
                $listM_s[$k]['username'] = $_name;
            }

            $this->assign("pageM_s", $showPageM_s);
            $this->assign("listM_s", $listM_s);
            $this->assign("pageM", $showPageM);
            $this->assign("listM", $listM);
            $this->assign("pageT", $showPageT);
            $this->assign("listT", $listT);
            $this->assign("pageS", $showPageS);
            $this->assign("listS", $listS);
            $this->assign("uid", $uid);
            $this->assign("userid", $cookieUser['user']);
            $this->assign('auth',cookie('auth'));
            $this->display('Details/emails1');
        }else {
            $this->error('请登录！');
        }

    }



    //会议资料申请成功
    public function meetsucc(){
        $Mf = M('MFormat');
        //海报图
        $fShow =  $Mf->field('f_show,f_show2')->where(array('f_confirm'=>1))->find();
        $this->assign('fshow', $fShow);
        $this->display('Details/meetsucc');
    }





    //自筹会议资料添加
    public function zichou1(){
        $id = I('get.id');
        $type = I('get.type');
        $D = D("Meeting");
        $user =  M('MUser');//获取用户信息
        $ms =  M('MStudent');
        $mt =  M('MTeacher');
        $Mr = M("MRoom"); //会议室表
        $M = M('MMt'); //会议类型
        $Mg = M('MGrade'); //会议等级
        $cookieUser = $this->getUser();
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
        /*获取用户信息*/
        if(IS_POST){
            if (empty($_POST['m_name'])) {
                $this->error('会议标题不能为空！');
            }

            if (empty($_POST['m_time'])) {
                $this->error('会议日期不能为空！');
            }

            if (empty($_POST['m_start'])) {
                $this->error('会议间隔开始时间不能为空！');
            }
            if (empty($_POST['m_end'])) {
                $this->error('会议间隔结束时间不能为空！');
            }

            if (empty($_POST['m_number'])) {
                $this->error('会议人数不能为空！');
            }

            if (empty($_POST['m_user'])) {
                $this->error('姓名不能为空！');
            }
/*
            if (empty($_POST['m_ylnumber'])) {
                $this->error('预留座位不能为空！');
            }
*/

            if (empty($_POST['m_phone'])) {
                $this->error('电话不能为空！');
            }
            $u_name = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_name'];
            $msName =  $ms->where(array('s_no'=>$u_name))->find()['s_name'];
            $mtName =  $mt->where(array('t_no'=>$u_name))->find()['t_name'];
            if (!empty($msName)) {
                $ms->where(array('s_no'=>$u_name))->setInc('s_num',1); // 申请次数加一
            }
            if (!empty($mtName)) {
                $mt->where(array('t_no'=>$u_name))->setInc('t_num',1); // 申请次数加一
            }

            try {
                $file_name = $this->upload($_FILES['m_images'],'images');
                $file_name_zilaio = $this->upload($_FILES['m_ziliao'],'ziliao','file');
                $_POST['m_images'] = $file_name;
                $_POST['m_ziliao'] = $file_name_zilaio;
                $_POST['time'] = time();

                $roomid = $_POST['id'];
                $roomexpire =  M('MRoom')->field('room_expire')->where(array('room_id'=>$roomid))->find();
                if (!empty($roomexpire['room_expire']) && $roomexpire['room_expire'] > 0) {
                    $ex =  time() - $roomexpire['room_expire'];
                    if ($ex >= 1200) {
                        M('MRoom')->where(array('room_id'=>$roomid))->save(array('room_expire'=>0));
                        $this->error('会议室选择20分钟后，会议申请未提交，从新选择！',U("/Index/shentable"));
                        return ;
                    }
                }

                if($data = $D->create(I('post.'))){
                    $re = $D->add($data);
                   // echo $D->getLastSql();exit;
                    if($re){
                        $Ms =  M('MSignup');
                        $data1['s_uid'] = $uid;
                        $data1['s_mid'] = $re;
                        $data1['s_time'] = time();
                        $Ms->add($data1);
                        $D->where(array('m_id'=>$re))->save(array('m_bianma'=>'hy00'.$re));
                        echo "<script type='text/javascript'>location.href='/Index/meetsucc.html'</script>";
                        return ;
                    }else{
                        throw new Exception('添加失败', 1);
                    }
                }else{
                    throw new Exception($D->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage());
            }

        }
        if ($id>0) {
            $oneMr = $Mr->where(array('room_id'=>$id))->find();
            if (empty($oneMr)) $this->error('错误！');
            $mtAll = $M->select();
            $gradeAll = $Mg->select();
            $this->assign('gradeAll', $gradeAll);
            $this->assign('mtAll', $mtAll);
            $this->assign('oneMr', $oneMr);
            $this->assign('uid', $uid);
            $this->assign('uname', $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_name']);
            $this->assign('type', $type);
            $this->assign('id', $id);
            $this->assign('date', I('get.date'));
            $this->assign('m_start', I('get.m_start'));
            $this->assign('m_end', I('get.m_end'));
            $this->display('Details/zichou1');
        }else {
            $this->error('错误！');
        }
    }



    //报名的会议
    public function baoming (){
        if (empty(cookie('auth'))) die('1');
        $user =  M('MUser');
        $Mt= M('Meeting');
        $Ms= M('MSignup');
        $M = M('MMt');
        $Mg = M('MGrade');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] !=1) {
            $uid = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }else {
            $uid = $user->where(array('u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }

        //得到报名的会议
        $Ms_all =  $Ms->where(array('s_uid'=>$uid))->field('s_mid')->select();
        foreach ($Ms_all as $k=>$v) {
            $Ms_all[$k] = $v['s_mid'];
        }
        $Ms_all_im = implode(',', $Ms_all);
        // echo $Ms_all_im;exit;
        $count = $Mt->where(array('m_a_status'=>1,'m_id'=>array('in',$Ms_all_im)))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mt->where(array('m_a_status'=>1,'m_id'=>array('in',$Ms_all_im)))->order('m_time DESC')->limit($page->firstRow , $page->listRows)->select();

        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
            $MtAll[$k]['m_address'] = M('MRoom')->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
        }
        $this->assign('MtAll',$MtAll);
        $this->assign("page", $showPage);
        $this->display('Details/baoming');

    }


    //参加的会议
    public function mymeet(){
        if (empty(cookie('auth'))) die('1');
        $user =  M('MUser');
        $Mt =  M('Meeting');
        $Ms =  M('MSignup');
        $M = M('MMt');
        $Mg = M('MGrade');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] !=1) {
            $uid = $user->where(array('u_name' => $cookieUser['user'], 'u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }else {
            $uid = $user->where(array('u_pwd' => $cookieUser['pwd']))->find()['u_id'];
        }

        //得到报名的会议
        $Ms_all =  $Ms->where(array('s_uid'=>$uid))->field('s_mid')->select();
        foreach ($Ms_all as $k=>$v) {
            $Ms_all[$k] = $v['s_mid'];
        }
        $Ms_all_im = implode(',', $Ms_all);
        $count = $Mt->where(array('m_a_status'=>1,'m_id'=>array('in',$Ms_all_im)))->count();
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $MtAll = $Mt->where(array('m_a_status'=>1,'m_id'=>array('in',$Ms_all_im)))->order('m_time DESC')->limit($page->firstRow , $page->listRows)->select();

        foreach ($MtAll as $k=>$v){
            $MtAll[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
            $MtAll[$k]['m_address'] = M('MRoom')->where(array('room_name'=>$v['m_rname']))->find()['room_address'];
        }
        $this->assign('MtAll',$MtAll);
        $this->assign("page", $showPage);
        $this->display('Details/mymeet');
    }


    //个人中心教师
    public function usercenter(){
        $Muser =  M('MUser');
        $mt =  M('MTeacher');
        $u_name =  $Muser->where(array('u_id'=>$this->uid))->find()['u_name'];
        if(IS_POST){
            try {
                if($data = $mt->create(I('post.'))){
                    $re = $mt->save($data);
                    if($re){
                        //$this->success("修改成功",U("/Index/usercenter"));
                        echo "<script type='text/javascript'>location.href= '/Index/usercenter.html';</script>";
                        return ;
                    }else{
                        echo "<script type='text/javascript'>location.href= '/Index/usercenter.html';</script>";
                        exit;
                        // throw new Exception('修改失败', 1);
                    }
                }else{
                    throw new Exception($mt->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        //教师
        if($this->type == 2) {
            $oneT =  $mt->where(array('t_no'=>$u_name))->find();
            $this->assign('oneT', $oneT);
            $this->display('Details/usercenter');
        }else {
            $this->error('非法操作！');
        }

    }

    //个人中心学生
    public function stusercenter(){
        $Muser =  M('MUser');
        $ms =  M('MStudent');
        $u_name =  $Muser->where(array('u_id'=>$this->uid))->find()['u_name'];
        if(IS_POST){
            try {
                if($data = $ms->create(I('post.'))){
                    $re = $ms->save($data);
                    if($re){
                        //$this->success("修改成功",U("/Index/stusercenter"));
                        echo "<script type='text/javascript'>location.href= '/Index/stusercenter.html';</script>";

                        return ;
                    }else{
                       // throw new Exception('修改失败', 1);
                        echo "<script type='text/javascript'>location.href= '/Index/stusercenter.html';</script>";

                    }
                }else{
                    throw new Exception($ms->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
        }
        //学生
        if($this->type == 3) {
            $oneS =  $ms->where(array('s_no'=>$u_name))->find();
            $this->assign('oneS', $oneS);
            $this->display('Details/stusercenter');
        }else {
            $this->error('非法操作！');
        }


    }


    //参加会议成功
    public function shensucc(){
        $this->display('Details/shensucc');
    }


    //校外参加会
    public function xiaowai(){
        $id = I('get.id');
        $Mt =  M('Meeting');
        $Muser =  M('MUser');
        $Ms =  M('MSignup');
        if (IS_AJAX) {

            if (empty($_POST['u_name'])) {
                die('-3');
            }


            if (empty($_POST['u_phone'])) {
                die('-5');

            }
            if (!empty($Muser->where(array('u_phone'=>$_POST['u_phone']))->find())) {
                die('-6');
            }


            try {
                $_POST['u_time'] = time();
                $_POST['u_pwd'] = md5(C("AUTH_CODE") . md5($_POST['u_phone']));
                $_POST['u_type'] = I('post.u_type');

                if($data = $Muser->create(I('post.'))){
                    $s_uid = $Muser->add($data);
                    /*写入cookie*/
                    //$u_name = I('post.u_name');
                    $u_pwd = $_POST['u_pwd'];
                    $u =  encrypt_en($u_pwd,c('MD5_KEY'));
                    $p =  encrypt_en($u_pwd,c('MD5_KEY'));
                    $t =  encrypt_en(I('post.u_type'),c('MD5_KEY'));
                    $jsonUser =  json_encode(array('u'=>$u,'p'=>$p,'t'=>$t));
                    cookie('auth', "$jsonUser", 60*60*24);
                    /*写入cookie*/
                    //   echo $D->getLastSql();exit;
                    if($s_uid){
                        $data1['s_uid'] = $s_uid;
                        $data1['s_mid'] = $_POST['m_id'];
                        $data1['s_time'] = time();
                        $Ms->add($data1);
                        die('-1');
                    }else{
                        die('-2');
                    }
                }else{
                    die('-2');
                }

            } catch (Exception $e) {
                die('-2');
            }

        }
        if ($id > 0) {
            $MtAll = $Mt->where(array('m_id'=>$id,'m_a_status'=>1))->find();
            if (empty($MtAll)) $this->error('错误！');


            $this->assign('id',$id);
            $this->assign('auth', cookie('auth'));
            $this->display('Details/xiaowai');
        }
    }


    //补登会议删除更改状态
    public function budengdel(){
        if (empty(cookie('auth'))) $this->error('请登录！');
        $user =  M('MUser');
        $D = D("Meeting");
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] == 2 || $cookieUser['type'] == 1) $this->error('您不能删除补登会议！');
        $meeting = $D->where(array('m_id'=>I('get.id')))->find();
        if (empty($meeting)) $this->error('没有找到补登会议！');
        $del = $D->where(array('m_id'=>I('get.id')))->save(array('m_del'=>1));
        if ($del >0) $this->success('删除成功！');
    }



    //补登会议列表
    public function budeng(){
        if (empty(cookie('auth'))) die('1');
        $D = D("Meeting");
        $user =  M('MUser');
        $M = M('MMt');
        $Mg = M('MGrade');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] == 2 || $cookieUser['type'] == 1) $this->error('您不能查看补登会议！');
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
        $count = $D->getList(I('get.'), 0, 0,1,1,$uid,1);
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $list = $D->getList(I('get.'),$page->firstRow, $page->listRows,0,1,$uid,1);

        foreach ($list as $k=>$v){
            $list[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
            $list[$k]['m_xuefen'] = $Mg->where(array('g_id'=>$v['m_grade']))->find()['g_xuefen'];
        }
        $this->assign("page", $showPage);
        $this->assign("list", $list);
        $this->display('Details/budeng');
    }


    //添加补登会议
    public function addbudeng(){
        $D = D("Meeting");
        $user =  M('MUser');
        $cookieUser = $this->getUser();
        if ($cookieUser['type'] == 2 || $cookieUser['type'] == 1) $this->error('您不能添加补登会议！');
        $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_id'];
        if(IS_POST){
            if (empty($_POST['m_name'])) {
                $this->error('会议名称不能为空！');
            }

            if (empty($_POST['m_time'])) {
                $this->error('会议时间不能为空！');
            }

            if (empty($_POST['m_address'])) {
                $this->error('会议地点不能为空！');
            }

            if (empty($_POST['m_content'])) {
                $this->error('会议内容不能为空！');
            }

            try {
                $_POST['time'] = time();
                $user =  M('MUser'); //用户表
                $uid = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd'],'u_type'=>$cookieUser['type']))->find()['u_id'];
                $ms =  M('MStudent');
                $mt =  M('MTeacher');
                $u_name = $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_name'];
                $msName_1 =  $ms->where(array('s_no'=>$u_name))->find()['s_name'];
                $mtName_1 =  $mt->where(array('t_no'=>$u_name))->find()['t_name'];
                if (!empty($msName_1)) {
                    $ms->where(array('s_no'=>$u_name))->setInc('s_num',1); // 申请次数加一
                }
                if (!empty($mtName_1)) {
                    $mt->where(array('t_no'=>$u_name))->setInc('t_num',1); // 申请次数加一
                }
                if($data = $D->create(I('post.'))){
                    $data['m_xingming'] = $this->u_name;
                    $data['m_no'] = $cookieUser['user'];
                    $re = $D->add($data);
                    if($re){
                        $this->success("添加成功",U("/Index/budeng"));
                        return ;
                    }else{
                        throw new Exception('添加失败', 1);
                    }
                }else{
                    throw new Exception($D->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage(),U("/Index/addbudeng"));
            }

        }
        $M = M('MMt');
        $mtAll = $M->select();
        $this->assign('mtAll', $mtAll);
        $this->assign("uid", $uid);
        $this->assign('uname', $user->where(array('u_name'=>$cookieUser['user'],'u_pwd'=>$cookieUser['pwd']))->find()['u_name']);
        $this->display('Details/addbudeng');
    }

    //退出登录
    public function logout (){
        cookie('auth',null);
        echo "<script type='text/javascript'>location.href= '/Index/index.html';</script>";exit;
    }



    //登录
	public function login(){
        if (IS_AJAX) {
            $u_name = I('post.u_name');
            $u_pwd = I('post.u_pwd');
            $yanzheng = I('post.yanzheng');
            $u_type = I('post.u_type');
            $muser = M('MUser');
            $msi = M('MStudentInfo');
            $mti = M('MTeacherInfo');
            $u_pwd =   md5(C("AUTH_CODE") . md5($u_pwd));
            if ($u_type == 1) {  //校外
                $_oneUser =  $muser->field('u_name,u_pwd')->where(array('u_phone'=>$u_name,'u_pwd'=>$u_pwd,'u_type'=>$u_type))->find();
            }else if ($u_type ==2) {  //教师
                $user =  $mti->where(array('t_no'=>$u_name,'t_pass'=>I('post.u_pwd')))->find();
                if (empty($user)) {
                    die('-2');
                }
                $_oneUser = $muser->field('u_name,u_pwd')->where(array('u_name'=>$u_name,'u_pwd'=>$u_pwd,'u_type'=>$u_type))->find();
                // echo $muser->getLastSql();exit;
                if (empty($_oneUser)) {
                    $data['u_name'] = $user['t_no'];
                    $data['u_time'] = time();
                    $data['u_type'] = 2;
                    $data['u_pwd'] = md5(C("AUTH_CODE") . md5($user['t_pass']));
                    $u_id = $muser->add($data);
                    $_oneUser = $muser->field('u_name,u_pwd')->where(array('u_name' => $u_name, 'u_pwd' => $u_pwd, 'u_type' => $u_type))->find();
                }

            }else if ($u_type == 3) { //学生
                $user =  $msi->where(array('s_no'=>$u_name,'s_pass'=>I('post.u_pwd')))->find();
                if (empty($user)) {
                    die('-2');
                }
                $_oneUser = $muser->field('u_name,u_pwd')->where(array('u_name'=>$u_name,'u_pwd'=>$u_pwd,'u_type'=>$u_type))->find();
                if (empty($_oneUser)) {
                    $data['u_name'] = $user['s_no'];
                    $data['u_time'] = time();
                    $data['u_type'] = 3;
                    $data['u_pwd'] = md5(C("AUTH_CODE") . md5($user['s_pass']));
                    $u_id = $muser->add($data);
                    $_oneUser = $muser->field('u_name,u_pwd')->where(array('u_name' => $u_name, 'u_pwd' => $u_pwd, 'u_type' => $u_type))->find();
                }


            }


            if (empty($_oneUser)) {
                die('-2');
            }
            if (cookie('verify') != md5($yanzheng)) {
                die('-3');
            }
            $u =  encrypt_en($u_name,c('MD5_KEY'));
            $p =  encrypt_en($u_pwd,c('MD5_KEY'));
            $t =  encrypt_en($u_type,c('MD5_KEY'));
            $jsonUser =  json_encode(array('u'=>$u,'p'=>$p,'t'=>$t));
            cookie('auth', "$jsonUser", 60*60*24);
            unset($u_pwd);
            die('-1');
        }

        if (empty(cookie('auth'))) {
            $this->display('Details/login');
        }
    }

	//自筹会议室
	public function zichou(){
        $D = D("MRoom");
        $Msh = M("MShape");
        if(IS_POST) {
            try {
                if (empty($_POST['room_name'])) {
                    $this->error('会议室名称不能为空！');
                }
                if (empty($_POST['room_address'])) {
                    $this->error('会议室地点不能为空！');
                }
                if (empty($_POST['room_number'])) {
                    $this->error('会议室是容纳人数不能为空！');
                }
                $_POST['room_time'] = time();
                if ($data = $D->create(I('post.'))) {
                    $re = $D->add($data);
                    if ($re) {
                        //$this->success("确认成功！", U("/Index/zichou1/id/$re"));
                        echo "<script type='text/javascript'>location.href='/Index/zichou1/id/".$re.".html'</script>";
                        return;
                    } else {
                        throw new Exception('确认失败', 1);
                    }
                } else {
                    throw new Exception($D->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage(), U("/Index/zichou1"));
            }
        }
        $this->assign('msh',$Msh->select());
        $this->assign('auth', cookie('auth'));
        $this->display('Details/zichou');
    }


    //上传
    private function upload($file='',$path = 'images',$type = 'img',$flag = 0){
        $tmp_file = $file['tmp_name'];
        $file_types = explode(".", $file['name'] );
        $file_type = $file_types[count($file_types) - 1];
        if ($flag == 0) {
            if ($type == 'img') {
                if ($file_type != 'png' && $file_type != 'jpg' && $file_type != 'gif' && $file_type != 'jpeg') {
                   // $this->error('图片格式错误！');
                }
            } else if ($type == 'file') {
                if ($file_type != 'doc' && $file_type != 'docx') {
                    //$this->error('文件格式错误！');
                }
            }
        }
        $savePath = SITE_PATH . '/public/upfile/'.$path.'/';
        $str = date ( 'Ymdhis' );
        $file_name = $str . "." . $file_type;
        if (!copy($tmp_file, $savePath . $file_name )) {
           // $this->error('上传失败' );
        }
        return $file_name;
    }
    //下载图片
    private function down_file($file_name,$file_sub_dir){
        $file_name=iconv("utf-8","gb2312",$file_name);
        $file_path=$_SERVER['DOCUMENT_ROOT'].$file_sub_dir.$file_name;
        if(!file_exists($file_path)){
            echo "文件不存在!";
            return ;
        }
        $fp=fopen($file_path,"r");
        $file_size=filesize($file_path);
        /* if($file_size>30){
             echo "<script language='javascript'>window.alert('过大')</script>";
             return ;
         }*/
        header("Content-type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: $file_size");
        header("Content-Disposition: attachment; filename=".$file_name);
        $buffer=1024;
        $file_count=0;
        while(!feof($fp) && ($file_size-$file_count>0) ){
            $file_data=fread($fp,$buffer);
            $file_count+=$buffer;
            echo $file_data;
        }
        fclose($fp);
    }
    //下载图片
    public function downImage (){
        $file = $_GET['file'];
        $this->down_file($file,'/Public/Images/Home/');
    }
}