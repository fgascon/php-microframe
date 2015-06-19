<?php

class MFViewController extends MFController
{
    
    public $layout = 'main';
    
    protected function processOutput($output)
    {
        if(!is_array($output))
            return $output;
        $view = isset($output['_view']) ? $output['_view'] : $this->getAction()->getUniqueId();
        return $this->renderView($view, $output);
    }
    
    protected function getViewsPath()
    {
        $reflector = new ReflectionClass(get_class($this));
        $controllerPath = $reflector->getFileName();
        if($controllerPath === false)
            return false;
        return realpath(dirname($controllerPath).'/../views');
    }
    
    public function renderView($view, $data=array())
    {
        $output = $this->renderPartialView($view, $data);
        $layout = isset($data['_layout']) ? $data['_layout'] : $this->layout;
        return $this->renderPartialView('layouts/'.$layout, array(
            'content'=>$output,
        ));
    }
    
    public function renderPartialView($view, $data=array())
    {
        $viewsPath = $this->getViewsPath();
        if(!$viewsPath)
            throw new Exception(MF::t('core', 'The views path cannot be retrieved.'));
        return $this->renderViewFile("$viewsPath/$view.php", $data);
    }
    
    protected function renderViewFile($__viewFile__, $__data__)
    {
        extract($__data__, EXTR_PREFIX_SAME, 'data');
        ob_start();
        ob_implicit_flush(false);
        require($__viewFile__);
        return ob_get_clean();
    }
}