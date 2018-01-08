<?php
/**
 * 会议补登审批
 */

class BdAction extends AdminAction{
    const  PAGE_PER_NUM = 20; //每页记录数

    /**
     * @var 会议补登审批
     */
    public function wei() {
        $D = D("Meeting");
        $count = $D->getList(I('get.'), 0, 0,1,1,0,0,false,2);
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $list = $D->getList(I('get.'),$page->firstRow, $page->listRows,0,1,0,0,false,2);
        $M = M('MMt');
        $Mg = M('MGrade');
        $Mu = M('MUser');

        /*所属机构*/
        $MM = D("MMechanism");
        foreach ($list as $k=>$v){
            $list[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
            $list[$k]['m_grade'] = $Mg->where(array('g_id'=>$v['m_grade']))->find()['g_name'];
            $list[$k]['m_xuefen'] = $Mg->where(array('g_id'=>$v['m_grade']))->find()['g_xuefen'];
            $MMaLL = $MM->where(array('m_id'=>$v['m_jigou']))->find()['m_name'];
            $list[$k]['m_jigou'] = $MMaLL;
            $list[$k]['m_uid'] = $Mu->where(array('u_id'=>$v['m_uid']))->find()['u_name'];
        }

        //print_r($list);exit;
        $this->assign("page", $showPage);
        $this->assign("list", $list);

        $this->display();
    }

    /**
     * @var 会议补登审批
     */
    public function yi() {
        $D = D("Meeting");
        $count = $D->getList(I('get.'), 0, 0,1,1,0,0,false,1);
        import("ORG.Util.Page"); //载入分页类
        $page = new Page($count,self::PAGE_PER_NUM);
        $showPage = $page->show();
        $list = $D->getList(I('get.'),$page->firstRow, $page->listRows,0,1,0,0,false,1);
        $M = M('MMt');
        $Mg = M('MGrade');
        $Mu = M('MUser');

        /*所属机构*/
        $MM = D("MMechanism");
        foreach ($list as $k=>$v){
            $list[$k]['m_type'] = $M->where(array('mt_id'=>$v['m_type']))->find()['mt_name'];
            $list[$k]['m_grade'] = $Mg->where(array('g_id'=>$v['m_grade']))->find()['g_name'];
            $list[$k]['m_xuefen'] = $Mg->where(array('g_id'=>$v['m_grade']))->find()['g_xuefen'];
            $MMaLL = $MM->where(array('m_id'=>$v['m_jigou']))->find()['m_name'];
            $list[$k]['m_jigou'] = $MMaLL;
            $list[$k]['m_uid'] = $Mu->where(array('u_id'=>$v['m_uid']))->find()['u_name'];
        }

        //print_r($list);exit;
        $this->assign("page", $showPage);
        $this->assign("list", $list);

        $this->display();
    }







    public function edit(){
        $D = D("Meeting");
        if (!I('get.id')){
            $this->error("非法参数");
            return ;
        }
        if(IS_POST){
            try {
                if (!empty($_FILES['m_images']['name'])) {
                    $file_name = $this->upload($_FILES['m_images'],'images');
                    $_POST['m_images'] = $file_name;
                }
                if (!empty($_FILES['m_ziliao']['name'])) {
                    $file_name_zilaio = $this->upload($_FILES['m_ziliao'],'ziliao','file');
                    $_POST['m_ziliao'] = $file_name_zilaio;

                }
                if($data = $D->create(I('post.'))){
                    $re = $D->save($data);
                    //echo $D->getLastSql();exit;
                    if($re){
                        $this->success("修改成功",U("/Admin/Bd/yi"));
                        return ;
                    }else{
                        throw new Exception('修改失败', 1);
                    }
                }else{
                    throw new Exception($D->getError(), 1);
                }

            } catch (Exception $e) {
                $this->error($e->getMessage(),U("/Admin/Bd/yi"));
            }
        }
        /*所属机构*/
        $MM = D("MMechanism");
        $MMaLL = $MM->where(array('m_parent'=>0))->select();
        $arr = array();
        foreach ($MMaLL as $k=>$v){
            $MMaLLSub = $MM->where(array('m_parent'=>$v['m_id']))->select();
            $MMaLL[$k]['m_sub'] = $MMaLLSub;

        }
        $this->assign('MMall', $MMaLL);
        $data = $D->find(I("get.id"));
        $M = M('MMt');
        $mtAll = $M->select();
        $this->assign('mtAll', $mtAll);
        $Mg = M('MGrade');
        $gradeAll = $Mg->select();
        $this->assign('gradeAll', $gradeAll);
        $this->assign('data',$data);
        $this->display();
    }







}