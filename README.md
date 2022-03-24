# langzige
使用示例：
use langzige\redis\RedisClient;


	 $redis = new RedisClient;

	 var_dump($redis->strValue('zhang','lishi1565643134'));
	 var_dump($redis->strValue('zhang'));
	 var_dump($redis->setList('list',json_encode(['zhangs'=>'ll'])));
	 var_dump($redis->setList('list','zhangs'));
	 var_dump($redis->getList('list',0,100));