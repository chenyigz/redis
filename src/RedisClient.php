<?php 
 /**
  * 使用redis常用扩展库
  */
declare(strict_types=1);
namespace langzige\redis;
class RedisClient{
	// 实列redis
	protected $redis;
	protected $config = [
		"hostname"	=>	'',
		"hostport"	=>	'',
		"password"	=>	'',
		"select"	=>	'',
	];

	// redis容器多列模式
	protected $instances=[];



	public function __construct(array $config=[])
	{
		try{

			$redisConfig =  config('database')['redis'];

			$this->config = array_merge($this->config,(array) $redisConfig,$config);

			$this->init();

		} catch(\Exception $e){

		}
	}


	public function __set($key,$value):void
	{
		$this->config[$key] = $value;
	}

	public function __get($key)
	{
		return array_key_exists($key,$this->config) ? $this->config[$key] : null;
	}

	/**
	 * 加载redis
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	public function init()
	{

		try{

			$redisToken = md5(json_encode($this->config));

			if(!array_key_exists($redisToken,$this->instances))
			{
				if(class_exists("Redis"))
				{
					$redis = new Redis;
					$redis->connect($this->config['hostname'],$this->config['hostport']);
					$this->config['password'] && $redis->auth($this->config['password']);
				} else {
					$redis = null;
				}

				$this->instances[$redisToken] = $redis;
			}
			$this->redis = $this->instances[$redisToken];
			// 处理选择库
			if($this->redis && array_key_exists("select",$this->config))
			{
				$this->redis->select($this->config['select']);
			}
		} catch(\Exception $e){

		}

		return $this;
	}


	/**
	 * 设置或者获取字符键对应值
	 * @param  [type] $key   [键，必须]
	 * @param  string $value [value值，可为空]
	 * @return [type]        [string|bool]
	 */
	public function strValue($key,$value=''): string|bool
	{
		if(!$this->redis || !$key || !is_string($key)) return false;
		return $value ? $this->redis->set($key,$value) : $this->redis->get($key) ; 
	}

	/**
	 * 设置列表的值
	 * @param [type] $key   [必须]
	 * @param string $value [必须]
	 */
	public function setList($key,string $value):int
	{
		if(!$this->redis) return false;
		return $this->redis->lpush($key, $value);
	}
	/**
	 * 获取列表
	 * @param  [type]  $key    [键名]
	 * @param  integer $amount [开始行]
	 * @param  integer $limit  [获取的条数]
	 * @return [type]          [数组]
	 */
	public function getList($key,$amount=0,$limit=100):array
	{
		if(!$this->redis) return false;
		return $this->redis->lrange($key, $amount ,$limit);
	}

	/**
	 * 获取加密hash键值
	 * @param  [type] $where [生成的条件]
	 * @return [type]        [description]
	 */
	public function getHashKey($where):string
	{
		return md5(json_encode($where));
	}

	/**
	 * 获取或者添加hash表值
	 * @param  [type] $field [hash表名]
	 * @param  [type] $where [字段]
	 * @param  boolean $value [不为时是获取，否则为设置]
	 * @return [type]         [description]
	 */
	public function hashValue($field,$where,$value=false){
		if(!$this->redis) return false;
		$whereToken = $this->getHashKey($where);
		if($value){

			return $this->redis->hset($field,$whereToken,json_encode($value));
		}
        $info = $this->redis->hget($field,$whereToken);
        $info && $info = json_decode($info,true);
        return $info;
	}
	/**
	 * 获取哈希表所有字段
	 * @param  [type]  $field [description]
	 * @return [type]         [description]
	 */
	public function hashValueAll($field)
	{
		if(!$this->redis) return false;
        return $this->redis->hgetall($field);
	}


	/**
	 * 删除指定hash表
	 * @param  [type] $field [hash表名]
	 * @param  [type] $where [字段]
	 * @return [type]        [description]
	 */
	public function hashDel($field,$where=false)
	{
		if(!$this->redis) return false;

		if(false !== $where)
		{
			return $this->redis->del($field,$this->getHashKey($where));
		}

		$number = 0;
		$data = $this->hashValueAll($field);
		if($data){
			foreach ($data as $key => $value) {
				if($this->redis->del($field,$key))
				{
					$number++;
				}
			}
		}

		return $number;
	}

	// 如果本客户端不存在就调用redis扩展
	public function __call($name,$argument){

		if($this->redis && method_exists($this->redis, $name))
		{
			return call_user_func_array([$this->redis,$name],$argument);
		}
		return false;
	}

	/**
	 * 静态调用
	 * @param  [type] $name     [description]
	 * @param  [type] $argument [description]
	 * @return [type]           [description]
	 */
	 public static function __callStatic($name,$argument){

	 	$static = new static;

		if(method_exists($static, $name))
		{
			return call_user_func_array([$static,$name],$argument);
		}
		return false;
	}

}