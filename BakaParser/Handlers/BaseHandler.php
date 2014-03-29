<?php
namespace BakaParser\Handlers;

/**
 * Description of BaseHandler
 *
 * @author David
 */
interface BaseHandler {
    public function __construct(\BakaParser\Modules\BaseModule $module, $parameters);
    public function output();
}
