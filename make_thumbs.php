<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

class PlgFabrik_ListMake_thumbs extends PlgFabrik_List
{
    public function button(&$args)
    {
        $model = $this->getModel();
        $params = $this->getParams();
        $tableName = $model->getTable()->db_table_name;
        $elementPlugin = FabrikWorker::getPluginManager();

        $elementIds = json_decode($params->get('list_thumb_elements'))->thumb_elements;

        foreach ($elementIds as $elementId) {
            $elementModel = $elementPlugin->getElementPlugin($elementId)->element;
            $elementModel->params = json_decode($elementModel->params);

            $isAjax = (bool) $elementModel->params->ajax_upload;

            $paths = $this->getPaths($elementModel->name, $tableName, $isAjax);

            foreach ($paths as $path) {
                if ($this->isPdf($path)) {
                    $path = str_replace('\\', '/', $path);
                    $fileName = end(explode('/', $path));
                    $fileNameThumb = str_replace('.pdf', '.png', $fileName);
                    $path_thumb = JPATH_BASE . '/images/stories/thumbs/' . $fileNameThumb;

                    if (!JFile::exists($path_thumb)) {
                        $width = $elementModel->params->thumb_max_width;
                        $height = $elementModel->params->thumb_max_height;
                        $this->makeThumb(JPATH_BASE . '/images/stories/' . $fileName, $path_thumb, $width, $height);
                    }
                }
            }
        }

        echo "Thumbs criados!";
        exit();
    }

    public function getPaths($elementName, $tableName, $isAjax) {
        $db = JFactory::getDbo();

        if (!$isAjax) {
            $query = $db->getQuery(true);
            $query->select($elementName)->from($tableName);
            $db->setQuery($query);
            $result = $db->loadColumn();
        }
        else {
            $query = $db->getQuery(true);
            $query->select($elementName)->from($tableName . '_repeat_' . $elementName);
            $db->setQuery($query);
            $result = $db->loadColumn();
        }

        return $result;
    }

    public function isPdf($path) {
        $ext = end(explode('.', $path));

        if ($ext === 'pdf') {
            return true;
        }

        return false;
    }

    public function makeThumb($path_pdf, $path_thumb, $width, $height) {
        $im = new Imagick($path_pdf . '[0]');
        $im->setImageFormat("png");
        $im->setImageBackgroundColor(new ImagickPixel('white'));
        $im->thumbnailImage($width, $height);
        $im->writeImage($path_thumb);

        if (JFile::exists($path_thumb)) {
            return true;
        }

        return false;
    }
}
