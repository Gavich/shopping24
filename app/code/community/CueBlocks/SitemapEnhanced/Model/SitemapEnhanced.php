<?php

/**
 * Description of SitemapEnhanced
 * @package   CueBlocks_SitemapEnhanced
 * @company   CueBlocks - http://www.cueblocks.com/
 * @author    Francesco Magazzu' <francesco.magazzu at cueblocks.com>
 */
class CueBlocks_SitemapEnhanced_Model_SitemapEnhanced extends Mage_Sitemap_Model_Sitemap {
    /**
     * Fix Byte for File Footer 
     *
     */

    Const FIX_BYTE = 9;

    /**
     * Fix names appended to files 
     *
     */
    Const CATEGORY_FILENAME = '_cat';
    Const PRODUCTS_FILENAME = '_prod';
    Const OUTOFSTOCK_FILENAME = '_prod_out';
//    Const MEDIA_FILENAME      = '_media';
    Const TAG_FILENAME = '_tags';
    Const REVIEW_FILENAME = '_reviews';
    Const CMS_FILENAME = '_cms';
    Const CUSTOM_PAGES_FILENAME = '_custom';
    Const DISALLOWED_FILENAME = '_disallowed_log';

    /**
     * Number of Links allowed for a single file
     *
     * @var string
     */
    protected $_linksLimit;

    /**
     *  Number of Bytes allowed for a single file
     *
     * @var string
     */
    protected $_bytesLimit;

    /**
     * Current Date
     *
     * @var string
     */
    protected $_date;

    /**
     * Base Url
     *
     * @var string
     */
    protected $_baseUrl;

    /**
     * helper
     *
     * @var CueBlocks_SitemapEnhanced_Helper_Data
     */
    protected $_helper;

    /**
     * disallowed url counter
     *
     * @var integer
     */
    protected $_disallowed;

    /**
     * disallowed log file
     *
     * @var Varien_Io_File
     */
    protected $_disallowed_log_file;

    /**
     * General Configuration
     *
     * @var Varien_Object
     */
    protected $_conf;
    protected $_sepFileCounter;

    public function _construct() {
        $this->_init('sitemapEnhanced/sitemapEnhanced');
    }

    /**
     * helper
     *
     * @return CueBlocks_SitemapEnhanced_Helper_Data
     */
    public function getDisallowedLogFile() {
        if (!$this->_disallowed_log_file) {

            $filename = $this->getHelper()->clearExtension($this->getSitemapFilename());
            $filename .= self::DISALLOWED_FILENAME . '.txt';

            $filePath = $this->getPath() . $filename;

// remove old log
            if (file_exists($filePath))
                $res = unlink($filePath);

            $this->_disallowed_log_file = new Varien_Io_File();
            $this->_disallowed_log_file->setAllowCreateFolders(true);
            $this->_disallowed_log_file->open(array('path' => $this->getPath()));

            $this->_disallowed_log_file->streamOpen($filename);
        }

        return $this->_disallowed_log_file;
    }

    /**
     * Skypped url log file
     *
     * @return Varien_Io_File
     */
    public function getHelper() {
        if (!$this->_helper)
            $this->_helper = Mage::helper('sitemapEnhanced');

        return $this->_helper;
    }

    /**
     * Domain url for the sitemap
     *
     * @return string
     */
    public function getDomain() {
        $url = Mage::helper('core')->htmlEscape(Mage::app()->getStore($this->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));

        return $url;
    }

    /**
     * Get a model for the file robots.txt
     *
     * @return CueBlocks_SitemapEnhanced_Model_Robots
     */
    protected function getRobots() {
        if (!$this->_robots)
            $this->_robots = Mage::getModel('sitemapEnhanced/robots');

        return $this->_robots;
    }

    /**
     * Get the Url of the index or sitemap file 
     *
     * @return array('filename','url')
     */
    public function getLinkForRobots($onlyUrl = false) {
        $collection = $this->getFilesCollection();


// Link for Search Engine ( index or sitemap )

        if ($collection->count() == 1) {

            $fileName = $collection->getFirstItem()->getSitemapFileFilename();
        } else if ($collection->count() > 1) {

            $collection = $this->getFilesCollection('index');
            $fileName = $collection->getFirstItem()->getSitemapFileFilename();
        }
        else
            $fileName = $this->getSitemapFilename();

        $fileName = preg_replace('/^\//', '', $this->getSitemapPath() . $fileName);
        $url = Mage::app()
                        ->getStore($this->getStoreId())
                        ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $fileName;
        if ($onlyUrl)
            return $url;
        else
            return array('filename' => $fileName, 'url' => $url);
    }

    public function getPath() {
        return parent::getPath();
    }

    /**
     * Get the report of the file generation
     *
     * @return string
     */
    public function getGenReport() {
        $msg = '';

        foreach ($this->_ioCollection as $item) {
            $name = $item->getSitemapFileFilename();
            $size = $item->getIo()->getSize();
            $links = $item->getIo()->getLinks();

            $msg .= "- $name (links: $links, size: $size bytes) <br/>";
        }

        if ($this->_disallowed) {
            $filename = $this->getHelper()->clearExtension($this->getSitemapFilename());
            $filename .= self::DISALLOWED_FILENAME . '.txt';

            $filepath = preg_replace('/^\//', '', $this->getSitemapPath() . $filename);
            $url = $this->getHelper()->htmlEscape(Mage::app()->getStore($this->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $filepath);

            $msg .= "<br/>$this->_disallowed pages were skipped in accord with your robots.txt rules. Please check file <a target=\"_blank\" href=\"$url\">$filename</a> for detail.<br/><br/>";

            $this->getDisallowedLogFile()->streamClose();
        }
        return $msg;
    }

    /**
     * Files Collections
     *
     * @return CueBlocks_SitemapEnhanced_Model_Mysql4_SitemapEnhancedFiles_Collection
     */
    public function getFilesCollection($type = null) {
        /* @var $collection CueBlocks_SitemapEnhanced_Model_Mysql4_SitemapEnhancedFiles_Collection */
        $collection = Mage::getModel('sitemapEnhanced/sitemapEnhancedFiles')
                ->getCollection()
                ->addFieldToFilter('sitemap_id', $this->getId())
                ->addOrder('sitemap_file_type', 'ASC');

        if ($type == 'index')
            $collection->addFieldToFilter('sitemap_file_type', 'index');

        return $collection;
    }

    protected function _beforeSave() {
        $io = new Varien_Io_File();
        $realPath = $io->getCleanPath(Mage::getBaseDir() . DS . $this->getSitemapPath());
        $_isCompressed = $this->getHelper()->getGeneralConf($this->getStoreId())->getUsecompression();

        /**
         * Check path is allow
         */
        if (!$io->allowedPath($realPath, Mage::getBaseDir())) {
            Mage::throwException(Mage::helper('sitemap')->__('Please define correct path'));
        }

        /**
         * Check exists and writeable path
         */
        if (!$io->fileExists($realPath, false)) {
            Mage::throwException(Mage::helper('sitemap')->__('Please create the specified folder "%s" before saving the sitemap.', Mage::helper('core')->htmlEscape($this->getSitemapPath())));
        }

        if (!$io->isWriteable($realPath)) {
            Mage::throwException(Mage::helper('sitemap')->__('Please make sure that "%s" is writable by web-server.', $this->getSitemapPath()));
        }
        /**
         * Check allow filename
         */
        if (!preg_match('#^[a-zA-Z0-9_\.]+$#', $this->getSitemapFilename())) {
            Mage::throwException(Mage::helper('sitemap')->__('Please use only letters (a-z or A-Z), numbers (0-9) or underscore (_) in the filename. No spaces or other characters are allowed.'));
        }

        if ($_isCompressed)
            $this->setSitemapFilename($this->getHelper()->clearExtension($this->getSitemapFilename()) . '.xml.gz');
        else
            $this->setSitemapFilename($this->getHelper()->clearExtension($this->getSitemapFilename()) . '.xml');

        if (!$this->getHelper()->isUnique($this)) {
            Mage::throwException(Mage::helper('sitemap')->__('Please select another filename/path, as another sitemap with same filename already exists on the specified location.'));
        }

        $this->setSitemapPath(rtrim(str_replace(str_replace('\\', '/', Mage::getBaseDir()), '', $realPath), '/') . '/');

        return Mage_Core_Model_Abstract::_beforeSave();
    }

    protected function _addFile($type = 'sitemap', $filename = null) {
        $_isCompressed = $this->_conf->getUsecompression();

        if ($filename == null) {
            $filename = $this->getSitemapFilename();
        }

        if ($type == 'index') {
            $filename = $this->getHelper()->clearExtension($filename);
            $filename = $filename . '_index.xml';
        } elseif ($type == 'sitemap' || $type == 'image') {
            $ext = $_isCompressed ? '.xml.gz' : '.xml';
            $filename = $this->getHelper()->clearExtension($filename);
            $filename = $filename . $ext;
        }

        $pathmap = $this->getHelper()->getGeneralConf($this->storeId)->getPathMap();
        /* @var $_file CueBlocks_SitemapEnhanced_Model_SitemapEnhancedFiles */
        $_file = Mage::getModel('sitemapEnhanced/sitemapEnhancedFiles');
        $_file->setSitemapId($this->getId());
        $_file->setSitemapFilePath($this->getPath() . $pathmap);
        $_file->setSitemapFileFilename($filename);
        $_file->setSitemapFileType($type);
        $_file->initIo($_isCompressed);

// close last file 
        $lastIo = $this->_ioCollection->getLastItem()->getIo();
        if ($lastIo)
            $lastIo->streamClose();

        $this->_ioCollection->addItem($_file);

        return $_file;
    }

    protected function _addFileHelper($append, $sepCounter, $type = 'sitemap') {
        $filename = $this->getHelper()->clearExtension($this->getSitemapFilename());
        $filename .= $append;
        $this->_addFile($type, $filename);

        if ($sepCounter) {
// reset the counter
            $this->_sepFileCounter = 1;
        }
    }

    protected function _addLink($xml, $type = 'sitemap', $useSepCounter = false) {
        $fileModel = $this->_ioCollection->getLastItem();
        $count = $useSepCounter ? $this->_sepFileCounter : $this->_ioCollection->count();

// check limits
        if ($type == 'sitemap' || $type == 'image') {

            $fileModelLinks = $fileModel->getIo()->getLinks();
            $fileModelBytes = $fileModel->getIo()->getSize();
// string bytes 
            $strByte = mb_strlen($xml, 'UTF-8');

            if (($strByte + $fileModelBytes) >= $this->_bytesLimit || ($fileModelLinks + 1) >= $this->_linksLimit) {
// add a new file
                $fileModelFilename = $this->getHelper()->clearExtension($fileModel->getSitemapFileFilename());
                $fileModelFilename = str_replace(array('_' . ($count - 1)), '', $fileModelFilename);
                $newFileName = $fileModelFilename . '_' . ($count);
                if ($useSepCounter)
                    $this->_sepFileCounter += 1;
                  $newFileName=$newFileName.'.xml';
                $this->_addFile($fileModel->getType(), $newFileName);
              
                $fileModel = $this->_ioCollection->getLastItem();
            }
            $this->setSitemapTotLinks($this->getSitemapTotLinks() + 1);
        }

        $fileModel->getIo()->streamWrite($xml);
        $fileModel->getIo()->increaseLinks(1);
        
    }

    protected function _initVar($forceConfReload = false) {
        $this->_conf = $this->getHelper()->getGeneralConf($this->getStoreId(), $forceConfReload);

// reset counter
        $this->setSitemapTotLinks(0);
        $this->setSitemapTagLinks(0);
        $this->setSitemapReviewLinks(0);
        $this->setSitemapCmsLinks(0);
        $this->setSitemapOutLinks(0);
        $this->setSitemapProdLinks(0);
        $this->setSitemapCatLinks(0);
        $this->setSitemapMediaLinks(0);
        $this->_disallowed = 0;

// set limits for splitting 
        $this->_bytesLimit = $this->_conf->getByteslimit() - CueBlocks_SitemapEnhanced_Model_SitemapEnhanced::FIX_BYTE;
//        $this->_bytesLimit = 10000; // for testing multi files generation ( index )
        $this->_linksLimit = $this->_conf->getLinkslimit();
//        $this->_linksLimit = 2;

        $this->_date = Mage::getSingleton('core/date')->gmtDate('Y-m-d');
        $this->_baseUrl = Mage::app()->getStore($this->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
        $this->_ioCollection = new Varien_Data_Collection();
    }

    /**
     * Generate XML file
     *
     * @return Mage_Sitemap_Model_Sitemap
     */
    public function generateXml($forceConfReload = false) {
        /* DEBUG */
//        Mage::log('-------------------------------------------------------------');
//        $totMem     = (int) str_replace('M', '', ini_get('memory_limit'));
//        $baseMemory = memory_get_usage();
//        Mage::log('FreeMem Before: ' . ( $totMem - ($baseMemory / 1024 / 1024)));
        $time_start = microtime(true);
        /* DEBUG */

// initialize variable
        $this->_initVar($forceConfReload);
// delete old files        
        $this->removeFiles();

        $productConf = $this->getHelper()->getProductConf($this->getStoreId());

        /* generation Start */
        $this->genCmsSite();
        $this->genCustomCmsSite();
        $this->genCatSite();
//        $this->genMediaSite();
        $this->genProdSite();
// out of stock
        $this->genProdOutSite();

        $this->genProdTagSite();
        $this->genProdReviewSite();

        if ($this->_ioCollection->count() > 1) {
            $this->genIndexSite();
        }

        $this->saveFilesModel();
        $this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
        $this->save();

        /* DEBUG */
//        $baseMemory = memory_get_usage();
//        Mage::log('FreeMem After: ' . ( $totMem - ($baseMemory / 1024 / 1024)));
        $time_end = microtime(true);
        $time = round(($time_end - $time_start), 2);
        /* DEBUG */

        // don't send mail if it a scheduled job
        if (!$forceConfReload)
        //send Email Report
            $this->getHelper()->sendEmailTemplate($this->getStoreId(), array('sitemap' => $this, 'frequency' => 'Push'));

        $msg = 'XML Sitemap Generation Summary:   <br/>' . $this->getGenReport() . 'Execution time: ' . $time . ' sec';
        return $msg;
    }

    /**
     * Generate categories sitemap
     */
    protected function genCatSite() {
        $categoryConf = $this->getHelper()->getCategoryConf($this->getStoreId());

        $enabled = (string) $categoryConf->getEnabled();

        if ($enabled) {

            $changefreq = (string) $categoryConf->getChangefreq();
            $priority = (string) $categoryConf->getPriority();

// excluded category list
//        $excludedCat = $categoryConf->getExcludedCategory() ? explode(',', $categoryConf->getExcludedCategory()) : null;

            $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_category')->getCollection($this->getStoreId());

            if ($queryCollection->rowCount() > 0) {
                

                $this->_addFileHelper(self::CATEGORY_FILENAME, TRUE);

                while ($categoryRow = $queryCollection->fetch()) {
                    $catId = $categoryRow['entity_id'];
                    $url = !empty($categoryRow['url']) ? $categoryRow['url'] : 'catalog/category/view/id/' . $catId;

                    $url = htmlspecialchars($this->_baseUrl . $url);

                    $tmpXml = '';
                    if ($changefreq)
                        $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                    if ($priority)
                        $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                    $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);

                    if ($this->_isUrlAllowed($url)) {
                        $this->_addLink($xml, 'sitemap', true);
                        $this->setSitemapCatLinks($this->getSitemapCatLinks() + 1);
                    }
                }

                unset($queryCollection);
            }
        }
    }

    /**
     * Generate products sitemap
     */
    protected function genProdSite() {


        $productConf = $this->getHelper()->getProductConf($this->getStoreId());


        $enabled = (string) $productConf->getEnabled();

        if ($enabled) {

            $changefreq = (string) $productConf->getChangefreq();
            $priority = (string) $productConf->getPriority();
            $imagesEnabled = (string) $productConf->getEnabledImages();
            // set filter for query
            $filterInStock = false;
            $filterOutOfStock = true;

            $type = $imagesEnabled ? 'image' : 'sitemap';

            $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_product')->getCollection($this->getStoreId(), $filterOutOfStock, $filterInStock);

            if ($queryCollection->rowCount() > 0) {

                // add a new file
                $this->_addFileHelper(self::PRODUCTS_FILENAME, TRUE, $type);

                while ($productRow = $queryCollection->fetch()) {
                    $prodId = $productRow['entity_id'];
                    $url = !empty($productRow['url']) ? $productRow['url'] : 'catalog/product/view/id/' . $prodId;
                    $url = htmlspecialchars($this->_baseUrl . $url);

                    $imgXml = '';

                    // image links
                    if ($imagesEnabled) {

                        $imagesqueryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_image')->getProdImageCollection($this->getStoreId(), $prodId);
                        $mediaUrl = Mage::app()->getStore($this->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
                        $imgXml = '';

                        if ($imagesqueryCollection->rowCount() > 0) {

                            while ($imageRow = $imagesqueryCollection->fetch()) {
//                        $label = $imageRow['label'];
                                $tmpXml = $this->_genImageTag($imageRow['path'],$mediaUrl);

//                        if ($label) {
//                            $labelXml = sprintf('<image:title>%s</image:title>', $label);
//                            $tmpXml .= $labelXml;
//                        }
                                $imgXml .= $tmpXml;
                                $this->setSitemapMediaLinks($this->getSitemapMediaLinks() + 1);
                            }
//                            $imgXml = sprintf('<image:image>%s</image:image>', $imgXml);
                        }
                    }

                    $tmpXml = '';
                    if ($changefreq)
                        $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                    if ($priority)
                        $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                    $tmpXml .= $imgXml;

                    $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);


                    if ($this->_isUrlAllowed($url)) {
                        $this->_addLink($xml, 'sitemap', true);

                        $this->setSitemapProdLinks($this->getSitemapProdLinks() + 1);
                    }
                }

                unset($queryCollection);
            }
        }
    }

    protected function _genImageTag($url,$mediaUrl) {

        $imgUrl = '';
        $xmlString = '';

        $imgUrl = $mediaUrl . 'catalog/product' . $url;
        $xmlString = sprintf('<image:image><image:loc>%s</image:loc></image:image>', $imgUrl);

        return $xmlString;
    }

    /**
     * Generate products sitemap
     */
    protected function genProdOutSite() {

        $productConf = $this->getHelper()->getProdOutConf($this->getStoreId());

        $enabled = (string) $productConf->getEnabled();

        if ($enabled) {

            $changefreq = (string) $productConf->getChangefreq();
            $priority = (string) $productConf->getPriority();
            $imagesEnabled = (string) $productConf->getEnabledImages();
            // set filter for query
            $filterOutOfStock = false;
            $filterInStock = true;

            $type = $imagesEnabled ? 'image' : 'sitemap';

            $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_product')->getCollection($this->getStoreId(), $filterOutOfStock, $filterInStock);

            if ($queryCollection->rowCount() > 0) {
                // add a new file
                $this->_addFileHelper(self::OUTOFSTOCK_FILENAME, TRUE, $type);

// Fix memory bug: collection approach was unsafe
                while ($productRow = $queryCollection->fetch()) {
                    $prodId = $productRow['entity_id'];
                    $url = !empty($productRow['url']) ? $productRow['url'] : 'catalog/product/view/id/' . $prodId;
                    $url = htmlspecialchars($this->_baseUrl . $url);

                    $imgXml = '';
// image links
                    if ($imagesEnabled) {

                        $imagesqueryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_image')->getProdImageCollection($this->getStoreId(), $prodId);
                        $mediaUrl = Mage::app()->getStore($this->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
                        $imgXml = '';

                        if ($imagesqueryCollection->rowCount() > 0) {

                            while ($imageRow = $imagesqueryCollection->fetch()) {
//                        $label = $imageRow['label'];
                                $tmpXml = $this->_genImageTag($imageRow['path'],$mediaUrl);

//                        if ($label) {
//                            $labelXml = sprintf('<image:title>%s</image:title>', $label);
//                            $tmpXml .= $labelXml;
//                        }
                                $imgXml .= $tmpXml;
                                $this->setSitemapMediaLinks($this->getSitemapMediaLinks() + 1);
                            }
                        }
                    }

                    $tmpXml = '';
                    if ($changefreq)
                        $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                    if ($priority)
                        $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                    $tmpXml .= $imgXml;

                    $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);

                    if ($this->_isUrlAllowed($url)) {
                        $this->_addLink($xml, 'sitemap', true);

                        $this->setSitemapOutLinks($this->getSitemapOutLinks() + 1);
                    }
                }

                unset($queryCollection);
            }
        }
    }

    /**
     * Generate products images sitemap
     */
//    protected function genMediaSite()
//    {
//
//        $mediaConf = $this->getHelper()->getMediaConf($this->getStoreId());
//
//        $enabled = (string) $mediaConf->getEnabled();
//
//        if ($enabled) {
//
//            $this->_addFileHelper(self::MEDIA_FILENAME, TRUE);
//
//            $productConf       = $this->getHelper()->getProductConf($this->getStoreId());
//            $includeOutOfStock = $productConf->getIncludeoutofstock();
//            $urlHelper         = Mage::getModel('catalog/product_media_config');
//
//            $changefreq = (string) $mediaConf->getChangefreq();
//            $priority   = (string) $mediaConf->getPriority();
//
//            $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_image')->getCollection($this->getStoreId(), $includeOutOfStock, $catId);
//
//            while ($imageRow = $queryCollection->fetch())
//            {
//                $url = $urlHelper->getMediaUrl($imageRow['path']);
//                $url = htmlspecialchars($url);
//
//                $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>', $url, $this->_date, $changefreq, $priority);
//
//                $this->_addLink($xml, 'sitemap', true);
//                $this->setSitemapMediaLinks($this->getSitemapMediaLinks() + 1);
//            }
//            unset($queryCollection);
//        }
//    }

    /**
     * Generate products tag sitemap
     */
    protected function genProdTagSite() {

        $tagConf = $this->getHelper()->getProdTagConf($this->getStoreId());

        $enabled = (string) $tagConf->getEnabled();

        if ($enabled) {

            $changefreq = (string) $tagConf->getChangefreq();
            $priority = (string) $tagConf->getPriority();

            $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_tag')->getCollection($this->getStoreId());

            if ($queryCollection->rowCount() > 0) {
                $this->_addFileHelper(self::TAG_FILENAME, TRUE);

                while ($tagRow = $queryCollection->fetch()) {
                    $tagId = $tagRow['tag_id'];
                    $tagRoute = 'tag/product/list/tagId/' . $tagId . '/';
                    $url = htmlspecialchars($this->_baseUrl . $tagRoute);

                    $tmpXml = '';
                    if ($changefreq)
                        $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                    if ($priority)
                        $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                    $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);

                    if ($this->_isUrlAllowed($url)) {
                        $this->_addLink($xml, 'sitemap', true);
                        $this->setSitemapTagLinks($this->getSitemapTagLinks() + 1);
                    }
                }
                unset($queryCollection);
            }
        }
    }

    /**
     * Generate products Review sitemap
     */
    protected function genProdReviewSite() {

        $revConf = $this->getHelper()->getProdReviewConf($this->getStoreId());

        $enabled = (string) $revConf->getEnabled();

        if ($enabled) {

            $changefreq = (string) $revConf->getChangefreq();
            $priority = (string) $revConf->getPriority();

            $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_review')->getCollection($this->getStoreId());

            if ($queryCollection->rowCount()) {
                $this->_addFileHelper(self::REVIEW_FILENAME, TRUE);

                while ($reviewRow = $queryCollection->fetch()) {
                    $prodId = $reviewRow['prod_id'];
                    $reviewRoute = 'review/product/list/id/' . $prodId . '/';
                    $url = htmlspecialchars($this->_baseUrl . $reviewRoute);

                    $tmpXml = '';
                    if ($changefreq)
                        $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                    if ($priority)
                        $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                    $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);
                    if ($this->_isUrlAllowed($url)) {
                        $this->_addLink($xml, 'sitemap', true);
                        $this->setSitemapReviewLinks($this->getSitemapReviewLinks() + 1);
                    }
                }
                unset($queryCollection);
            }
        }
    }

    protected function genCmsSite() {
        /**
         * Generate cms pages sitemap
         */
        $cmsConf = $this->getHelper()->getCmsConf($this->getStoreId());

        $enabled = (string) $cmsConf->getEnabled();

        if ($enabled) {

            $exPagesId = explode(',', $cmsConf->getExcludedPages());
            $changefreq = (string) $cmsConf->getChangefreq();
            $priority = (string) $cmsConf->getPriority();

            /* Varien_Object */
            $collection = Mage::getResourceModel('sitemap/cms_page')->getCollection($this->getStoreId());

            if (count($collection) > 0) {

                $this->_addFileHelper(self::CMS_FILENAME, TRUE);

                foreach ($collection as $item) {
                    if (in_array($item->getId(), $exPagesId))
                        continue;

                    $tmpXml = '';
                    if ($changefreq)
                        $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                    if ($priority)
                        $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                    $url = htmlspecialchars($this->_baseUrl . $item->getUrl());

                    $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);

                    if ($this->_isUrlAllowed($url)) {
                        $this->_addLink($xml, 'sitemap', true);
                        $this->setSitemapCmsLinks($this->getSitemapCmsLinks() + 1);
                    }
                }

                unset($collection);
            }
        }
    }

    protected function genCustomCmsSite() {
        /**
         * Generate cms pages sitemap
         */
        $customConf = $this->getHelper()->getCustomPagesConf($this->getStoreId());

        $enabled = (string) $customConf->getEnabled();

        if ($enabled) {

            $this->_addFileHelper(self::CUSTOM_PAGES_FILENAME, TRUE);

            $links = explode(';', $customConf->getLinks());
            $changefreq = (string) $customConf->getChangefreq();
            $priority = (string) $customConf->getPriority();

            foreach ($links as $url) {

                if (trim($url) == '')
                    continue;
                $url = htmlspecialchars($url);

                $tmpXml = '';
                if ($changefreq)
                    $tmpXml = sprintf('<changefreq>%s</changefreq>', $changefreq);
                if ($priority)
                    $tmpXml .= sprintf('<priority>%.1f</priority>', $priority);

                $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod>%s</url>', $url, $this->_date, $tmpXml);

                if ($this->_isUrlAllowed($url)) {
                    $this->_addLink($xml, 'sitemap', true);
                    $this->setSitemapCmsLinks($this->getSitemapCmsLinks() + 1);
                }
            }

            unset($links);
        }
    }

    /**
     * Generate cms pages sitemap
     */
    protected function genIndexSite() {

        $this->_addFile('index');

        $collection = $this->_ioCollection;

        foreach ($collection as $item) {
            if ($item->getSitemapFileType() != 'index') {
                $fileName = preg_replace('/^\//', '', $this->getSitemapPath() . $item->getSitemapFileFilename());
                $url = Mage::app()->getStore($this->getStoreId())->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . $fileName;
                $url = $this->getHelper()->escapeHtml($url);

                $xml = sprintf('<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>', $url, $this->_date);
                $this->_addLink($xml, 'index');
            }
        }
        unset($collection);
    }

    protected function saveFilesModel() {
        /**
         * Save Collection to db and close files ( also <tag> )
         */
        $collection = $this->_ioCollection;

        foreach ($collection as $item) {
            $item->save();
            $item->getIo()->streamClose();
        }
    }

    public function ping() {

        $subConf = $this->getHelper()->getPingConf($this->getStoreId());
        $searchEngine = explode(',', $subConf->getSearchEngine());

        $fileUrl = $this->getLinkForRobots();
        $fileName = $fileUrl['filename'];
        $url = $fileUrl['url'];


        if (file_exists(BP . DS . $fileName)) {

            $msg = "";

// check yahoo key 
            if (!$subConf->getYahooKey() && ( $key = array_search("Yahoo", $searchEngine) )) {
                unset($searchEngine[$key]);
                $msg = $msg . '- Skipping Yahoo. (You need to provide an API key to submit your sitemap to Yahoo)<br/>';
            }

            foreach ($searchEngine as $engineName) {
                $methodName = '_ping' . $engineName;

                if ($code = call_user_func(array($this, $methodName), $url)) {
                    $msg = $msg . '- Sent to ' . $engineName . ' (CODE: ' . $code . ') - OK <br/>';
                } else {
                    $msg = $msg . '- Failed to sent to ' . $engineName . '- check system.log for detail. <br/>';
                }
            }


            return 'url: ' . $url . "<br/>" . $msg;
        } else {
            throw new Mage_Core_Exception("Sitemap could not be found in required location: " . BP . DS . $fileName);
        }
    }

    protected function _pingGoogle($url) {
        $ping = "http://www.google.com/webmasters/sitemaps/ping?sitemap=" . urlencode($url);
        return $this->_makeRequest($ping);
    }

    protected function _pingBing($url) {
        $ping = "http://www.bing.com/webmaster/ping.aspx?siteMap=" . urlencode($url);
        return $this->_makeRequest($ping);
    }

    protected function _pingAsk($url) {
        $ping = "http://submissions.ask.com/ping?sitemap=" . urlencode($url);
        return $this->_makeRequest($ping);
    }

    protected function _makeRequest($ping) {

        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            'timeout' => 20
        ));

        $curl->write(Zend_Http_Client::GET, $ping, '1.1');
        $data = $curl->read();

        if ($data === false) {
            return false;
        }

        $code = $curl->getInfo(CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $code;
        } else {
            Mage::log("Submission to: " . $ping . " failed, HTTP response code was not 200");
            Mage::log("Response error: " . $data); // uncomment to debug raw submission response
            return false;
        }

//TODO: handle timeout?
    }

    public function removeFiles() {

        $collection = $this->getFilesCollection();
        $pathmap = $this->getHelper()->getGeneralConf($this->storeId)->getPathMap();

        foreach ($collection as $item) {
            $filePath = $this->getPath() . $pathmap . $item->getSitemapFileFilename();
            if (file_exists($filePath))
                $res = unlink($filePath);

            $item->delete();
        }
    }

    public function addToRobots() {
        $fileUrl = $this->getLinkForRobots();
        $fileName = $fileUrl['filename'];
        $url = $fileUrl['url'];

        if (file_exists(BP . DS . $fileName)) {

            if ($this->getRobots()->hasPermission()) {
                $ret = $this->getRobots()->addSitemap($url);
                return $ret;
            } else {
                throw new Mage_Core_Exception("Robots.txt has wrong permission is not possible to read/write it.");
            }
        } else {
            throw new Mage_Core_Exception("Sitemap could not be found in required location: " . BP . DS . $fileName);
        }
    }

    /**
     * Check if the url is allowed in the robots.txt 
     * ( if this function is enabled in config ) 
     *
     */
    protected function _isUrlAllowed($url) {
        if ($this->getHelper()->getGeneralConf($this->getStoreId())->getParseRobots()) {
            $isAllowed = $this->getRobots()->isAllowed($url);

            if (!$isAllowed) {
                $this->_disallowed += 1;
                $io = $this->getDisallowedLogFile();
                $io->streamWrite($url . "\n");
            }

            return $isAllowed;
        }
        else
            return true;
    }

    /**
     * Generate categories sitemap
     */
    /*
      protected function genSepCatSite()
      {

      $categoryConf = $this->getHelper()->getCategoryConf($this->getStoreId());
      $productConf  = $this->getHelper()->getProductConf($this->getStoreId());

      $includeOutOfStock  = $productConf->getIncludeoutofstock();
      $outOfStockSeparate = $includeOutOfStock ? $productConf->getSeparateoutofstock() : false;

      $changefreq = (string) $categoryConf->getChangefreq();
      $priority   = (string) $categoryConf->getPriority();

      //excluded category list
      $excludedCat = $categoryConf->getExcludedCategory() ? explode(',', $categoryConf->getExcludedCategory()) : null;

      $queryCollection = Mage::getResourceModel('sitemapEnhanced/catalog_category')->getCollection($this->getStoreId(), $excludedCat);

      while ($categoryRow = $queryCollection->fetch())
      {

      $catId = $categoryRow['entity_id'];

      // add a new file
      $catName  = preg_replace('/[^a-zA-Z0-9\.]/', '_', $categoryRow['name']); // remove all non-alphanumeric chars
      $filename = $this->getHelper()->clearExtension($this->getSitemapFilename());
      $filename .= '_' . $catName;
      $this->_sepFileCounter = 1;
      $this->_addFile('sitemap', $filename);

      $url = !empty($categoryRow['url']) ? $categoryRow['url'] : 'catalog/category/view/id/' . $catId;
      $xml = sprintf('<url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%.1f</priority></url>', htmlspecialchars($this->_baseUrl . $url), $this->_date, $changefreq, $priority);

      $this->_addLink($xml);

      // add category products list
      if (!$includeOutOfStock || $outOfStockSeparate)
      $this->genProdSite($catId, true);
      else
      $this->genProdSite($catId);

      $this->genMediaSite($catId);
      }
      unset($queryCollection);
      } */
}
