<?php
namespace Skolar\Handlers;

/**
 * Description of BaseHandler
 *
 * @author David
 */
interface BaseHandler {
    public function __construct(\Skolar\Modules\BaseModule $module, $parameters);
    public function output();
}
