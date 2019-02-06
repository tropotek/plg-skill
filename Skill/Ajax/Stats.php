<?php
namespace Skill\Ajax;


/**
 * Url format: /ems/ajax/company/createMapMarker.html?companyId=73
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Stats
{

    /**
     * @param \Tk\Request $request
     * @return \Tk\ResponseJson
     * @throws \Exception
     */
    public function doCalendarData(\Tk\Request $request)
    {
        $response = array();


        vd($request);




        return \Tk\ResponseJson::createJson($response);

    }

}