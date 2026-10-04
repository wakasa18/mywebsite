<?php
require dirname(__DIR__,2).'/system/Test/bootstrap.php';
set_exception_handler(static function(Throwable $e):void {fwrite(STDERR,$e->getMessage()."\n");exit(1);});
$routes=service('routes');$checked=0;$issues=[];
foreach(['GET','POST','PUT','PATCH','DELETE'] as $verb){
    foreach($routes->getRoutes($verb) as $uri=>$target){
        if(!is_string($target)||!str_contains($target,'::'))continue;
        [$class,$method]=explode('::',$target,2);
        $class=ltrim($class,'\\');$method=explode('/',$method)[0];
        if(!str_starts_with($class,'App\\'))$class='App\\Controllers\\'.$class;
        $expected=ROOTPATH.'app/'.str_replace('\\','/',substr($class,4)).'.php';
        $dir=ROOTPATH;$exact=true;
        foreach(explode('/',substr($expected,strlen(ROOTPATH))) as $part){
            if(!is_dir($dir)||!in_array($part,scandir($dir),true)){$exact=false;break;}
            $dir=rtrim($dir,'/\\').'/'.$part;
        }
        $checked++;
        if(!$exact||!class_exists($class)||!method_exists($class,$method)||!(new ReflectionMethod($class,$method))->isPublic())$issues[]=['verb'=>$verb,'uri'=>$uri,'target'=>$target];
    }
}
$db=\Config\Database::connect('default');
$schema=[];
foreach(['users'=>['session_version'],'sales'=>['checkout_token_hash'],'refund_items'=>['return_condition','refund_method','refund_event_id'],'suppliers'=>['email']] as $table=>$columns){
    foreach($columns as $column)$schema["$table.$column"]=$db->fieldExists($column,$table);
}
$data=['route_targets_checked'=>$checked,'route_issues'=>$issues,'local_schema'=>$schema,'auto_routing'=>config('Routing')->autoRoute];
file_put_contents(__DIR__.'/deployment-routes.json',json_encode($data,JSON_PRETTY_PRINT));
echo json_encode($data,JSON_PRETTY_PRINT),"\n";
