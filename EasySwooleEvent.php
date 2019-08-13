<?php
namespace EasySwoole\EasySwoole;
use App\Process\HotReload;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use App\WebSocket\WebSocketEvent;
use App\WebSocket\WebSocketParser;
use EasySwoole\Socket\Dispatcher;

class EasySwooleEvent implements Event
{

	public static function initialize()
	{
		date_default_timezone_set( 'Asia/Shanghai' );
	}

	public static function mainServerCreate( EventRegister $register )
	{
		define( 'PROJECT_PATH', __DIR__.DIRECTORY_SEPARATOR );
		define( 'APP_PATH', __DIR__.DIRECTORY_SEPARATOR.'App' );

		if (Core::getInstance()->isDev()) {
            $swooleServer = ServerManager::getInstance()->getSwooleServer();
            $swooleServer->addProcess((new HotReload('HotReload', ['disableInotify' => false]))->getProcess());
        }
		\ezswoole\Core::register();
		/**
		 * **************** websocket控制器 **********************
		 */
		// 创建一个 Dispatcher 配置
		$conf = new \EasySwoole\Socket\Config();
		// 设置 Dispatcher 为 WebSocket 模式
		$conf->setType( $conf::WEB_SOCKET );
		// 设置解析器对象
		$conf->setParser( new WebSocketParser() );
		// 创建 Dispatcher 对象 并注入 config 对象
		$dispatch = new Dispatcher( $conf );
		// 给server 注册相关事件 在 WebSocket 模式下  message 事件必须注册 并且交给 Dispatcher 对象处理
		$register->set( EventRegister::onMessage, function( \swoole_websocket_server $server, \swoole_websocket_frame $frame ) use ( $dispatch ){
			$dispatch->dispatch( $server, $frame->data, $frame );
		} );
		//自定义握手
		$websocketEvent = new WebSocketEvent();
		$register->set( EventRegister::onHandShake, function( \swoole_http_request $request, \swoole_http_response $response ) use ( $websocketEvent ){
			$websocketEvent->onHandShake( $request, $response );
		} );
		$register->set( EventRegister::onClose, function( \swoole_server $server, int $fd, int $reactorId ) use ( $websocketEvent ){
			$websocketEvent->onClose( $server, $fd, $reactorId );
		} );


		$register->add( EventRegister::onWorkerStart, function( \swoole_server $server, $workerId ){

		} );

	}

	public static function onRequest( Request $request, Response $response ) : bool
	{
		return true;
	}

	public static function afterRequest( Request $request, Response $response ) : void
	{
	}
}

