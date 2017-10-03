<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of gcFeatureQGis
 *
 * @author geosim2
 */
class gcFeatureQGis extends gcFeature {

    /**
     * @var array $qFeature QGis feature data
     */
    private $qFeature;
    /**
     * [$layertype description]
     * @var array $layertypes array from e_layertype table in gisclient database
     */
    private $layertypes = array();

    /**
     * [__destruct description]
     */
    function __destruct() {
        parent::__destruct();
    }

    /**
     * [__construct description]
     * @param [type] $i18n [description]
     */
    function __construct($i18n = null) {
        parent::__construct($i18n);
        $stmt = $this->db->query('SELECT layertype_id, layertype_name FROM ' . DB_SCHEMA . '.e_layertype');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->layertypes[$row['layertype_id']] = ucwords(trim($row['layertype_name']));
        }
    }

    /**
     * [initFeature description]
     * @param  [type] $layerId [description]
     * @return [type]          [description]
     */
    public function initFeature($layerId) {
        parent::initFeature($layerId);
        $this->qFeature = parent::getFeatureData();
        list($usec, $sec) = explode(' ', microtime());
        $this->qFeature['qgis_id'] = $this->qFeature['layer_name'] . date('YmdHis', $sec) . substr($usec, 2, 6);
    }

    public function getFeatureData() {
        return $this->qFeature;
    }

    /**
     * Set feature data
     *
     * @param array $qFeature
     */
    public function setFeatureData(array $qFeature) {
        $this->qFeature = $qFeature;
    }

    /**
     * [getFeatureTreeNode description]
     * @return DOMDocument XML entry for QGis layer tree reperesenting single layer
     */
    public function getFeatureTreeNode($nodeChecked) {
        $resDom = new DOMDocument;
        $treeElem = $resDom->createElement('layer-tree-layer');
        $treeElem->setAttribute('expanded', '1');
        $treeElem->setAttribute('checked', $nodeChecked?'Qt::Checked':'Qt::Unchecked');
        $treeElem->setAttribute('id', $this->qFeature['qgis_id']);
        $treeElem->setAttribute('name', $this->qFeature['layer_name']); // **** O layer title?
        $custPropElem = $resDom->createElement('customproperties');
        $treeElem->appendChild($custPropElem);
        $resDom->appendChild($treeElem);
        return $resDom;
    }

    /**
     * [getFeatureLegendNode description]
     * @return DOMDocument XML entry for QGis legend reperesenting single layer
     */
    public function getFeatureLegendNode($nodeChecked) {
        $resDom = new DOMDocument;
        $legendElem = $resDom->createElement('legendlayer');
        $legendElem->setAttribute('drawingOrder', '-1');
        $legendElem->setAttribute('open', 'true');
        $legendElem->setAttribute('checked', $nodeChecked?'Qt::Checked':'Qt::Unchecked');
        $legendElem->setAttribute('name', $this->qFeature['layer_name']); // **** O layer title?
        $legendElem->setAttribute('showFeatureCount', '0');

        $filegroupElem = $resDom->createElement('filegroup');
        $filegroupElem->setAttribute('open', 'true');
        $filegroupElem->setAttribute('hidden', 'false');

        $legendlayerElem = $resDom->createElement('legendlayerfile');
        $legendlayerElem->setAttribute('isInOverview', '0');
        $legendlayerElem->setAttribute('id', $this->qFeature['qgis_id']);
        $legendlayerElem->setAttribute('visible', '1');

        $filegroupElem->appendChild($legendlayerElem);
        $legendElem->appendChild($filegroupElem);
        $resDom->appendChild($legendElem);

        return $resDom;
    }

    /**
     * [getFeatureMapNode description]
     * @return DOMDocument XML entry for QGis maplayer reperesenting single layer
     */
    public function getFeatureMapNode ($layerData) {
        $resDom = new DOMDocument;

        // **** Max and min scale
        $maxScale = "1e+08";
        $minScale = "0";
        if ($layerData) {
            if (!empty($this->qFeature['maxscale']))
                $maxScale = $this->qFeature['maxscale'];
            else if (!empty($layerData['layergroup_maxscale']))
                $maxScale = $layerData['layergroup_maxscale'];

            if (!empty($this->qFeature['minscale']))
                $minScale = $this->qFeature['minscale'];
            else if (!empty($layerData['layergroup_minscale']))
                $minScale = $layerData['layergroup_minscale'];
        }

        $maplayerElem = $resDom->createElement('maplayer');
        $maplayerElem->setAttribute('minimumScale', $minScale);
        $maplayerElem->setAttribute('maximumScale',$maxScale);
        $maplayerElem->setAttribute('simplifyDrawingHints',"0");
        $maplayerElem->setAttribute('minLabelScale', $minScale);
        $maplayerElem->setAttribute('maxLabelScale', $maxScale);
        $maplayerElem->setAttribute('simplifyDrawingTol',"1");
        $maplayerElem->setAttribute('geometry', $this->layertypes[$this->qFeature['layertype_id']]);
        $maplayerElem->setAttribute('simplifyMaxScale',"1");
        $maplayerElem->setAttribute('type',"vector");
        $maplayerElem->setAttribute('hasScaleBasedVisibilityFlag',"1");
        $maplayerElem->setAttribute('simplifyLocal',"1");
        $maplayerElem->setAttribute('scaleBasedLabelVisibilityFlag',"0");

        // **** Set QGis ID
        $idElem =  $resDom->createElement('id', $this->qFeature['qgis_id']);
        $maplayerElem->appendChild($idElem);

        // **** Set datasource
        $connString = $this->_getLayerConnection();
        $dsElem =  $resDom->createElement('datasource', $connString);
        $maplayerElem->appendChild($dsElem);

        // **** Keywordlist
        $kwElem = $resDom->createElement('keywordList');
        $kwvElem = $resDom->createElement('value', '');
        $kwElem->appendChild($kwvElem);
        $maplayerElem->appendChild($kwElem);

        // ** layername
        $lnameElem = $resDom->createElement('layername', $this->qFeature['layer_name']); // **** O layer title?
        $maplayerElem->appendChild($lnameElem);

        // **** src
        $srcElem = $resDom->createElement('src');
        $refsysDom = $this->getSpatialRefSysNode($this->qFeature['data_srid']);
        $refsysRes = $refsysDom->getElementsByTagName('spatialrefsys')->item( 0 );
        $refsysImport = $resDom->importNode($refsysRes, TRUE);
        $srcElem->appendChild($refsysImport);
        $maplayerElem->appendChild($srcElem);

        // **** Provider
        $prElem = $resDom->createElement('provider', 'postgres'); // **** TODO: handle other providers
        $prElem->setAttribute('encoding', 'System');
        $maplayerElem->appendChild($prElem);

        // **** Edittypes
        $fieldsDom = $this->_getEditTypesNode();
        $fieldsRef = $fieldsDom->getElementsByTagName('edittypes')->item(0);
        $fieldsImport = $resDom->importNode($fieldsRef, TRUE);
        $maplayerElem->appendChild($fieldsImport);

        //**************************************************
        //**** STYLES

        //**** create container elements
        //****  LABELS:
        $labelRules = $resDom->createElement('rules');

        //**** STYLES
        $styleRules = $resDom->createElement('rules');
        $styleSymbols = $resDom->createElement('symbols');

        $sql = "select pattern_id,symbol_name,class_id,layer_id,class_name,class_title,class_text,expression,maxscale,minscale,class_template,class_order,legendtype_id,symbol_ttf_name,
                label_font,label_angle,label_color,label_outlinecolor,label_bgcolor,label_size,label_minsize,label_maxsize,label_position,label_antialias,label_free,label_priority,
                label_wrap,label_buffer,label_force,label_def,keyimage,style_id,style_name,color,outlinecolor,bgcolor,angle,size,minsize,maxsize,width,maxwidth,minwidth,
                style_def,style_order,symbolcategory_id,icontype,symbol_def,symbol_type,font_name,ascii_code,filled,points,image,pattern_name,pattern_def,pattern_order
                from gisclient_3.class c
                left join gisclient_3.style s using (class_id)
                left join gisclient_3.symbol using (symbol_name)
                left join gisclient_3.e_pattern using(pattern_id)
                where c.layer_id=?
                order by style_order";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->qFeature['layer_id']]);
        $res = $stmt->fetchAll();

        $styleIdx = 0;
        foreach ($res as $styleDef){
            // **** Build rule common element
            $commonRule = $resDom->createElement('rule');

            if (isset($styleDef['expression'])) {
                $ruleFilter = trim($styleDef['expression']);
                if (substr($ruleFilter, 0, 1) == '(' && substr($ruleFilter, -1) == ')') { // **** Mapserver Expression
                    $ruleFilter = substr($ruleFilter, 1, -1);
                    $ruleFilter = str_replace(array("'[","]'"), "", $ruleFilter);
                    $commonRule->setAttribute('filter', $ruleFilter);
                }
                else if (substr($ruleFilter, 0, 1) == '/' && substr($ruleFilter, -1) == '/') { // **** Mapserver regexp
                    if (isset($this->qFeature['labelitem'])) {
                        $ruleFilter = substr($ruleFilter, 1, -1);
                        $ruleFilter = $this->qFeature['labelitem'] . " ~ '" . $ruleFilter . "'";
                        $commonRule->setAttribute('filter', $ruleFilter);
                    }
                }
                else if (substr($ruleFilter, 0, 1) == "'" && substr($ruleFilter, -1) == "'") { // **** Mapserver string match - TODO: string list
                    if (isset($this->qFeature['labelitem'])) {
                        $ruleFilter = $this->qFeature['labelitem'] . " = " . $ruleFilter;
                        $commonRule->setAttribute('filter', $ruleFilter);
                    }
                }
            }

            if (isset($styleDef['maxscale'])) {
                $commonRule->setAttribute('scalemaxdenom', $styleDef['maxscale']);
                if (!isset($styleDef['minscale'])) {
                    $commonRule->setAttribute('scalemindenom', '1');
                }
            }

            if (isset($styleDef['minscale'])) {
                $commonRule->setAttribute('scalemindenom', $styleDef['minscale']);
                if (!isset($styleDef['maxscale'])) {
                    $commonRule->setAttribute('scalemaxdenom', '1e+08');
                }
            }

            $commonRule->setAttribute('description', $styleDef['class_title']);

            // ****  Build label element
            if (isset($styleDef['class_text']) || isset($this->qFeature['labelitem'])) {
                $labelText = isset($styleDef['class_text'])?$styleDef['class_text']:$this->qFeature['labelitem'];
                $labelText = str_replace(array("'[","]'"), "", $labelText);
                $labelExpr = '0';
                if (false !== strpos($labelText, '+')) { // **** TODO: handle other operator, not only concat
                    $labelText = str_replace('+', ',', $labelText);
                    $labelText = 'concat(' . $labelText . ')';
                    $labelExpr = '1';
                }

                if (QGIS_FORCE_FONT) {
                    $labelFont = QGIS_FONT;
                }
                else {
                    $labelFont = isset($styleDef['label_font'])?$styleDef['label_font']:QGIS_FONT;
                }

                $labelColor = isset($styleDef['label_color'])?str_replace(' ', ',', $styleDef['label_color']):'0,0,0';
                $labelColor .= ',255';

                $labelRule = $commonRule->cloneNode(TRUE);
                $labelSettings = $resDom->createElement('settings');
                $labelTextStyle = $resDom->createElement('text-style');
                // **** TODO: other attribute translated from mapserver?
                $labelTextStyle->setAttribute("fontItalic","0");
                $labelTextStyle->setAttribute("fontFamily",$labelFont);
                $labelTextStyle->setAttribute("fontLetterSpacing","0");
                $labelTextStyle->setAttribute("fontUnderline","0");
                $labelTextStyle->setAttribute("fontWeight","50");
                $labelTextStyle->setAttribute("fontStrikeout","0");
                $labelTextStyle->setAttribute("textTransp","0");
                $labelTextStyle->setAttribute("previewBkgrdColor","#ffffff");
                $labelTextStyle->setAttribute("fontCapitals","0");
                $labelTextStyle->setAttribute("textColor",$labelColor);
                $labelTextStyle->setAttribute("fontSizeInMapUnits","0");
                $labelTextStyle->setAttribute("isExpression",$labelExpr);
                $labelTextStyle->setAttribute("blendMode","0");
                $labelTextStyle->setAttribute("fontSizeMapUnitScale","0,0,0,0,0,0");
                $labelTextStyle->setAttribute("fontSize","9");
                $labelTextStyle->setAttribute("fieldName",$labelText);
                $labelTextStyle->setAttribute("namedStyle","Normal");
                $labelTextStyle->setAttribute("fontWordSpacing","0");

                $labelSettings->appendChild($labelTextStyle);

                // **** Label position:
                switch (strtolower($styleDef['label_position'])) {
                    case 'ul':
                    $quadOffset = '0';
                    break;
                    case 'uc':
                    $quadOffset = '1';
                    break;
                    case 'ur':
                    $quadOffset = '2';
                    break;
                    case 'cl':
                    $quadOffset = '3';
                    break;
                    case 'cc':
                    $quadOffset = '4';
                    break;
                    case 'cr':
                    $quadOffset = '5';
                    break;
                    case 'll':
                    $quadOffset = '6';
                    break;
                    case 'lc':
                    $quadOffset = '7';
                    break;
                    case 'lr':
                    $quadOffset = '8';
                    break;
                }

                if (isset($quadOffset)) {
                    $labelPlacement = $resDom->createElement('placement');
                    $labelPlacement->setAttribute("repeatDistanceUnit","1");
                    $labelPlacement->setAttribute("placement","1");
                    $labelPlacement->setAttribute("maxCurvedCharAngleIn","20");
                    $labelPlacement->setAttribute("repeatDistance","0");
                    $labelPlacement->setAttribute("distInMapUnits","1");
                    $labelPlacement->setAttribute("labelOffsetInMapUnits","1");
                    $labelPlacement->setAttribute("xOffset","0");
                    $labelPlacement->setAttribute("distMapUnitScale","0,0,0,0,0,0");
                    $labelPlacement->setAttribute("predefinedPositionOrder","TR,TL,BR,BL,R,L,TSR,BSR");
                    $labelPlacement->setAttribute("preserveRotation","1");
                    $labelPlacement->setAttribute("repeatDistanceMapUnitScale","0,0,0,0,0,0");
                    $labelPlacement->setAttribute("centroidWhole","0");
                    $labelPlacement->setAttribute("priority","0");
                    $labelPlacement->setAttribute("yOffset","0");
                    $labelPlacement->setAttribute("offsetType","0");
                    $labelPlacement->setAttribute("placementFlags","0");
                    $labelPlacement->setAttribute("centroidInside","0" );
                    $labelPlacement->setAttribute("dist","0");
                    $labelPlacement->setAttribute("angleOffset","0");
                    $labelPlacement->setAttribute("maxCurvedCharAngleOut","-20");
                    $labelPlacement->setAttribute("fitInPolygonOnly","0");
                    $labelPlacement->setAttribute("quadOffset",$quadOffset);
                    $labelPlacement->setAttribute("labelOffsetMapUnitScale","0,0,0,0,0,0");

                    $labelSettings->appendChild($labelPlacement);
                }

                // **** rotations
                if (isset($styleDef['label_angle'])) {
                    $labelAngle = $styleDef['label_angle'];
                    $labelDataDefined = $resDom->createElement('data-defined');
                    $labelRotation = $resDom->createElement('Rotation');
                    if (substr($labelAngle, 0, 1) == '[' && substr($labelAngle, -1) == ']') { // **** Database field TODO: regexp, handle other cases
                        $labelRotation->setAttribute('expr', '');
                        $labelRotation->setAttribute('field', substr($labelAngle, 1, -1));
                        $labelRotation->setAttribute('active', 'true');
                        $labelRotation->setAttribute('useExpr', 'false');
                    }

                    $labelDataDefined->appendChild($labelRotation);
                    $labelSettings->appendChild($labelDataDefined);
                }

                $labelRule->appendChild($labelSettings);
                $labelRules->appendChild($labelRule);

            }

            // ****  Build style element
            $styleRule = $commonRule->cloneNode(TRUE);
            $styleRule->setAttribute('symbol', "$styleIdx");
            $styleRule->setAttribute('name', $styleDef['class_name']);
            $styleRule->setAttribute('label', $styleDef['class_name']);
            $styleRules->appendChild($styleRule);

            $styleSymbol = $resDom->createElement('symbol');
            $styleSymbol->setAttribute("alpha", "1");
            $styleSymbol->setAttribute("clip_to_extent", "1");
            $styleSymbol->setAttribute("name", "$styleIdx");

            $styleLayer = $resDom->createElement('layer');
            $styleLayer->setAttribute('pass', '0');
            $styleLayer->setAttribute('locked', '0');

            $styleColor = isset($styleDef['color'])?str_replace(' ', ',', $styleDef['color']).',255':'0,0,0,255';
            $styleOlColor = isset($styleDef['outlinecolor'])?str_replace(' ', ',', $styleDef['outlinecolor']).',255':'0,0,0,0';
            $styleBgColor = isset($styleDef['bgcolor'])?str_replace(' ', ',', $styleDef['bgcolor']).',255':'0,0,0,0';

            switch ($this->qFeature['layertype_id']) { // TODO: retrieve layer (geometry) types from db
                case 1:
                case 5:
                    $styleSymbol->setAttribute('type', 'marker');
                    if (preg_match('/^TYPE TRUETYPE.*FONT "([^"]*)"/', $styleDef['symbol_def'], $fontMatches)) {
                        $markerFont = $fontMatches[1];
                    }
                    else {
                        $markerFont = $styleDef['font_name'];
                    }
                    if (preg_match('/^TYPE TRUETYPE.*CHARACTER "([^"]*)"/', $styleDef['symbol_def'], $charMatches)) {
                        $markerChar = html_entity_decode($charMatches[1], ENT_QUOTES);
                    }
                    else {
                        $markerChar = chr($styleDef['ascii_code']);
                    }
                    if ($markerFont != null && $markerChar != null) {
                        $styleLayer->setAttribute('class', 'FontMarker');
                        $this->_addStyleLayerProp($resDom, $styleLayer, 'font', $markerFont);
                        $this->_addStyleLayerProp($resDom, $styleLayer, 'chr', $markerChar);
                        $this->_addStyleLayerProp($resDom, $styleLayer, 'size', QGIS_FONTMARKER_SIZE);
                        $this->_addStyleLayerProp($resDom, $styleLayer, "vertical_anchor_point", "2");
                        $this->_addStyleLayerProp($resDom, $styleLayer, "offset", "0,-0.5");
                        $styleOlColor = $styleColor;
                    }
                    else { // **** TODO: other symbol types?
                        $styleLayer->setAttribute('class', 'SimpleMarker');
                        $this->_addStyleLayerProp($resDom, $styleLayer, 'name', 'circle');
                        // **** no marker for labels
                        if (!isset($styleDef['style_id'])) {
                            $this->_addStyleLayerProp($resDom, $styleLayer, 'size', '0');
                        }
                        else {
                            $this->_addStyleLayerProp($resDom, $styleLayer, 'size', QGIS_SIMPLEMARKER_SIZE); // default; TODO: settings from geoweb?
                        }
                        $this->_addStyleLayerProp($resDom, $styleLayer, "vertical_anchor_point", "1");
                        $this->_addStyleLayerProp($resDom, $styleLayer, "offset", "0,0");

                    }

                    // **** Common attributes
                    $this->_addStyleLayerProp($resDom, $styleLayer, "angle", "0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "horizontal_anchor_point", "1");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "offset_map_unit_scale", "0,0,0,0,0,0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "offset_unit", "MM");

                    // **** Set color
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'color', $styleColor);
                    if ($styleOlColor != '0,0,0,0') {

                        $this->_addStyleLayerProp($resDom, $styleLayer, 'outline_width', QGIS_OUTLINE_WIDTH);
                    }
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'outline_color', $styleOlColor);
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'outline_style', 'solid');
                    $this->_addStyleLayerProp($resDom, $styleLayer, "outline_width_map_unit_scale", "0,0,0,0,0,0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "outline_width_unit", "MM");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "scale_method", "diameter");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "size_map_unit_scale", "0,0,0,0,0,0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "size_unit", "MM");

                    // **** rotations
                    // **** rotations
                    if (isset($styleDef['angle'])) {
                        $labelAngle = $styleDef['angle'];
                        if (substr($labelAngle, 0, 1) == '[' && substr($labelAngle, -1) == ']') { // **** Database field TODO: regexp, handle other cases
                            $this->_addStyleLayerProp($resDom, $styleLayer, "angle_dd_active", "1");
                            $this->_addStyleLayerProp($resDom, $styleLayer, "angle_dd_expression", "360-" . substr($labelAngle, 1, -1));
                            $this->_addStyleLayerProp($resDom, $styleLayer, "angle_dd_field", substr($labelAngle, 1, -1));
                            $this->_addStyleLayerProp($resDom, $styleLayer, "angle_dd_useexpr", "1");
                        }

                    }
                    break;
                case 2:
                    $styleSymbol->setAttribute('type', 'line');
                    $styleLayer->setAttribute('class', 'SimpleLine');
                    // **** set line pattern
                    $customDash = "5;2";
                    $useCustomDash = "0";
                    if (preg_match('/^PATTERN ([0-9 ]*) END$/', $styleDef['pattern_def'], $charMatches)) {
                        $customDash = str_replace(' ', ';', trim($charMatches[1]));
                        $useCustomDash = "1";
                    }
                    $this->_addStyleLayerProp($resDom, $styleLayer, "capstyle", "square");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "customdash", $customDash);
                    $this->_addStyleLayerProp($resDom, $styleLayer, "customdash_map_unit_scale", "0,0,0,0,0,0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "customdash_unit", "MM");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "draw_inside_polygon", "0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "joinstyle", "bevel");
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'line_color', $styleColor);
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'line_style', 'solid');
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'line_width', QGIS_LINE_WIDTH);
                    $this->_addStyleLayerProp($resDom, $styleLayer, "line_width_unit", "MM");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "offset", "0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "offset_map_unit_scale", "0,0,0,0,0,0");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "offset_unit", "MM");
                    $this->_addStyleLayerProp($resDom, $styleLayer, "use_custom_dash", $useCustomDash);
                    $this->_addStyleLayerProp($resDom, $styleLayer, "width_map_unit_scale", "0,0,0,0,0,0");
                    break;

                case 3:
                    $styleSymbol->setAttribute('type', 'fill');
                    $styleLayer->setAttribute('class', 'SimpleFill');
                    if ($styleColor == '0,0,0,255')
                        $styleColor = '0,0,0,0';
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'color', $styleColor);
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'outline_color', $styleOlColor);
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'outline_style', 'solid');
                    $this->_addStyleLayerProp($resDom, $styleLayer, 'outline_width', QGIS_OUTLINE_WIDTH);
                    break;
            }

            $styleSymbol->appendChild($styleLayer);
            $styleSymbols->appendChild($styleSymbol);

            $styleIdx++;
        }

        //**** create base tags
        //****  LABELS:
        if ($labelRules->getElementsByTagName('rule')->item( 0 ) !== null) {
            $labelBaselElem = $resDom->createElement('labeling');
            $labelBaselElem->setAttribute('type', 'rule-based');
            $labelBaselElem->appendChild($labelRules);
            $maplayerElem->appendChild($labelBaselElem);
        }

        //****  STYLES:
        if ($styleRules->getElementsByTagName('rule')->item( 0 ) !== null) {
            $styleBaselElem = $resDom->createElement('renderer-v2');
            $styleBaselElem->setAttribute('forceraster', '0');
            $styleBaselElem->setAttribute('symbollevels', '0');
            $styleBaselElem->setAttribute('type', 'RuleRenderer');
            $styleBaselElem->setAttribute('enableorderby', '0'); // TODO: consider style order?
            $styleBaselElem->appendChild($styleRules);
            $styleBaselElem->appendChild($styleSymbols);
            $maplayerElem->appendChild($styleBaselElem);
        }

        $resDom->appendChild($maplayerElem);
        return $resDom;
    }

    /**
     * [_getLayerConnection description]
     * @return [type] [description]
     */
    private function _getLayerConnection() {
        $layerConnStr = '';
        if ($this->qFeature["layertype_id"] == 10 && !$this->qFeature["tileindex"]) {//TILERASTER

        }
        else {
            switch ($this->qFeature["connection_type"]) {
                case MS_SHAPEFILE: //Local folder shape and raster

                    break;

                case MS_WMS:

                    break;

                case MS_WFS:

                    break;

                case MS_POSTGIS:
                    if ($this->qFeature['layertype_id'] == 2) {
                        $layerType = 'LineString';
                    }
                    else {
                        $layerType = $this->layertypes[$this->qFeature['layertype_id']];
                    }
                    $tblQuery = $this->_getLayerData();
                    $tblQuery = substr($tblQuery, 13);
                    $tblQuery = substr($tblQuery, 0, -7);
                    $keyField = "'gc_objid'";

                    // **** Handle duplicate keys (dirty mode):
                    try {
                        $layerConn = new GCDataDB($this->qFeature['catalog_path']);
                        $stmt = $layerConn->db->query("SELECT count(gc_objid) AS test_dup FROM " . $tblQuery . " AS test_table GROUP BY gc_objid HAVING count(gc_objid)>1");
                        $res = $stmt->fetchAll();
                        if (count($res) > 0 ) {
                            $layerConn->db->query('DROP MATERIALIZED VIEW IF EXISTS ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name']);
                            $tblQuery = str_replace('SELECT', 'SELECT row_number() over () as qg_objid,', $tblQuery);
                            $layerConn->db->query('CREATE MATERIALIZED VIEW ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . ' AS ' . $tblQuery . ' WITH DATA');
                            $layerConn->db->query('CREATE UNIQUE INDEX qgis_' . $this->qFeature['layer_name'] . '_idx ON ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . ' (qg_objid)');
                            $layerConn->db->query('CREATE INDEX qgis_' . $this->qFeature['layer_name'] . '_geom ON ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . '  USING gist(gc_geom)');
                            $layerConn->db->query('GRANT SELECT ON TABLE ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . ' TO ' . MAP_USER);
                            // **** Create SQL
                            if (defined('QGIS_SQL_PATH') && QGIS_SQL_PATH) {
                                $sqlFilePath = QGIS_SQL_PATH . '/' . $this->qFeature['catalog_id'] . '.' . $this->qFeature['layer_name'] . '.sql';
                                $sqlFileContent = '--' . $this->qFeature['connection_string'] . "\n";
                                $sqlFileContent .= 'DROP MATERIALIZED VIEW IF EXISTS ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . ";\n";
                                $sqlFileContent .= 'CREATE MATERIALIZED VIEW ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . ' AS ' . $tblQuery . " WITH DATA;\n";
                                $sqlFileContent .= 'CREATE UNIQUE INDEX qgis_' . $this->qFeature['layer_name'] . '_idx ON ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . " (qg_objid);\n";
                                $sqlFileContent .= 'CREATE INDEX qgis_' . $this->qFeature['layer_name'] . '_geom ON ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . "  USING gist(gc_geom);\n";
                                $sqlFileContent .= 'GRANT SELECT ON TABLE ' . QGIS_LAYERS_SCHEMA . '.' . $this->qFeature['layer_name'] . ' TO ' . MAP_USER . ';';

                                if (false === ($f = fopen ($sqlFilePath,"w"))) {
                                    $errorMsg = "Could not open $sqlFilePath for writing";
                                    GCError::register($errorMsg);
                                    return;
                                }
                                if (false === (fwrite($f, $sqlFileContent))) {
                                    $errorMsg = "Could not write to $sqlFilePath";
                                    GCError::register($errorMsg);
                                    return;
                                }
                                fclose($f);

                            }
                            $tblQuery = QGIS_LAYERS_SCHEMA . '"."' . $this->qFeature['layer_name'];
                            $keyField = "'qg_objid'";
                        }
                    }
                    catch (PDOException $Exception) {
                        $connError = "Errore nella definizione del maplayer " . $this->qFeature['layer_name'] . ":\n";
                        $connError .= $Exception->getMessage();
                        GCError::register($connError);
                    }

                    $layerConnStr = $this->qFeature['connection_string'];
                    $layerConnStr = str_replace('localhost', $_SERVER['SERVER_NAME'], $layerConnStr);
                    $layerConnStr .= " sslmode=disable key=" . $keyField;
                    //$layerConnStr .= " sslmode=disable key='qg_objid'";
                    $layerConnStr .= " srid=" . $this->qFeature['data_srid'];
                    $layerConnStr .= " type=" . $layerType;
                    $layerConnStr .= ' table="' . $tblQuery . '" (gc_geom)';

                    //$tblQuery = str_replace('SELECT', 'SELECT row_number() over () as qg_objid,', $tblQuery);
                    //$layerConnStr .= $tblQuery . '" (gc_geom)';
                    if ($this->qFeature['data_filter'])
                        $layerConnStr .= ' sql=' . $this->qFeature['data_filter'];
                    break;

                case MS_ORACLESPATIAL:

                    break;

                case MS_SDE:
                    break;

                case MS_OGR:

                    break;
                case MS_GRATICULE:
                    break;
                case MS_MYGIS:
                    break;
                    break;
                case MS_PLUGIN:
                    break;
            }
        }
        return $layerConnStr;
    }

    /**
     * [getSpatialRefSysNode description]
     * @param  int $srid srid of layer/project
     * @return DOMDocument      DOM node for QGIS spatialrefsys entry
     */
    public function getSpatialRefSysNode($srid) {
        $resDom = new DOMDocument;
        if (!$srid) {
            return $resDom;
        }

        $sql =  'SELECT auth_name, auth_srid, proj4text,
                        array_to_string(regexp_matches(srtext, \'^[^"]*"([^"]*)\'), \';\') AS description,
                        array_to_string(regexp_matches(proj4text, \'proj=([^ ]*)\'), \';\') AS projectionacronym,
                        array_to_string(regexp_matches(proj4text, \'ellps=([^ ]*)\'), \';\') AS ellipsoidacronym
                        FROM spatial_ref_sys WHERE srid=?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$srid]);
        $res = $stmt->fetchAll();

        if (count($res) != 1) {
            return $resDom;
        }
        $srsData = $res[0];

        $srsItem = $resDom->createElement('spatialrefsys');
        $proj4Item = $resDom->createElement('proj4', $srsData['proj4text']);
        $srsItem->appendChild($proj4Item);
        $sridItem = $resDom->createElement('srid', $srsData['auth_srid']);
        $srsItem->appendChild($sridItem);
        $authIdItem = $resDom->createElement('authid', $srsData['auth_name'] . ':' . $srsData['auth_srid']);
        $srsItem->appendChild($authIdItem);
        $descItem = $resDom->createElement('description', $srsData['description']);
        $srsItem->appendChild($descItem);
        $projAcrItem = $resDom->createElement('projectionacronym', $srsData['projectionacronym']);
        $srsItem->appendChild($projAcrItem);
        $elAcrItem = $resDom->createElement('ellipsoidacronym', $srsData['ellipsoidacronym']);
        $srsItem->appendChild($elAcrItem);

        $resDom->appendChild($srsItem);
        return $resDom;

    }

    /**
     * [_getEditTypesNode description]
     * @return DOMDocument      DOM node for QGIS edittypes entry
     */
    private function _getEditTypesNode() {
        $resDom = new DOMDocument;

        $etElem = $resDom->createElement('edittypes');

        foreach ($this->qFeature['fields'] as $field) {
            $etField = $resDom->createElement('edittype');
            $etField->setAttribute('widgetv2type', 'TextEdit'); // **** TODO: map mapserver/qGis field type?
            $etField->setAttribute('name', $field['field_name']);
            $widgetElem = $resDom->createElement('widgetv2config');
            $widgetElem->setAttribute('IsMultiline', '0');
            $widgetElem->setAttribute('fieldEditable', '1');
            $widgetElem->setAttribute('UseHtml', '0');
            $widgetElem->setAttribute('labelOnTop', '0');

            $etField->appendChild($widgetElem);
            $etElem->appendChild($etField);
        }

        $resDom->appendChild($etElem);
        return $resDom;
    }

    private function _addStyleLayerProp(&$domObj, &$layerObj, $prop, $value) {
        $valElem = $domObj->createElement('prop');
        $valElem->setAttribute('k', $prop);
        $valElem->setAttribute('v', $value);
        $layerObj->appendChild($valElem);
    }

}
