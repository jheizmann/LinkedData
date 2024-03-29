<?php
/*
 * Copyright (C) Vulcan Inc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @ingroup LODSpecialPage
 * @ingroup SpecialPage
 */
class LODLinkSpecsPage extends SpecialPage {

    public function __construct() {
        parent::__construct('LODLinkSpecs');
        wfLoadExtensionMessages('LODLinkSpecs');
    }

    function execute($p) {
        global $wgOut;
        global $wgScript;
        global $lodgScriptPath, $lodgStyleVersion;
	    global $lodgSilkServerUrl;

        SMWOutputs::requireHeadItem("lod_linkspec.css",
                        '<link rel="stylesheet" type="text/css" href="' . $lodgScriptPath . '/skins/linkspec.css'.$lodgStyleVersion.'" />');

        SMWOutputs::commitToOutputPage($wgOut);
        $this->setHeaders();

        $html = "<iframe id=\"silkFrame\" src=\"".$lodgSilkServerUrl."\" width=\"1100px\"  height=\"1150px\"  >You need a Frames Capable browser to view this content.</iframe>";

        $wgOut->addHTML($html);

       }



}
