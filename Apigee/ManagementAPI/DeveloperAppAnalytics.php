<?php
/**
 * @file
 * Exposes Developer App Analytics data from the Management API.
 *
 * @author djohnson
 */

namespace Apigee\ManagementAPI;

/**
 * Exposes Developer App Analytics data from the Management API.
 *
 * @author djohnson
 */
class DeveloperAppAnalytics extends Analytics
{
    /**
     * After ensuring params are valid, fetches analytics data.
     *
     * @param string $devEmailOrCompany
     *    The email of the developer or company name that owns the app. If you
     *    do not pass in this parameter you will get analytics for any app
     *    in the org with this app name, since app name is not unique across
     *    developers.
     * @param string $appName
     *    The name of the app.
     * @param string $metric
     *    A value of 'message_count', 'message_count-first24hrs',
     *    'message_count-second24hrs', 'error_count', 'error_count-first24hrs',
     *    'total_response_time', 'max_response_time', or 'min_response_time'.
     * @param string $tStart
     *    Time start, expressed as:
     *    <ul>
     *      <li>UNIX timestamp</li>
     *      <li>mm/dd/YYYY hh:ii</li>
     *      <li>Any other format that the underlying strtotime() PHP function
     *        can parse. See {@link http://php.net/strtotime}.
     *        It parses them out to a UNIX timestamp if possible, otherwise
     *        it throws an exception.</li>
     *    </ul>
     * @param string $tEnd
     *    Time end, expressed as:
     *    <ul>
     *      <li>UNIX timestamp</li>
     *      <li>mm/dd/YYYY hh:ii</li>
     *      <li>Any other format that the underlying strtotime() PHP function
     *        can parse. See {@link http://php.net/strtotime}.
     *        It parses them out to a UNIX timestamp if possible, otherwise
     *        it throws an exception.</li>
     *    </ul>
     * @param string $tUnit
     *    Time unit: a value of 'second', 'minute', 'hour', 'day', 'week',
     *    'month', 'quarter', or 'year'.
     * @param string $sortBy
     *    A comma separated list of the same values as $metric.
     * @param string $sortOrder
     *    Either 'ASC' or 'DESC'.
     * @param bool $is_company
     *    If TRUE get app for company, otherwise for developer.
     *
     * @return array
     *   An array of analytic data points.
     *
     * @throws \Apigee\Exceptions\ParameterException
     *   Thrown in case of an invalid parameter.
     */
    public function getByAppName($devEmailOrCompany, $appName, $metric, $tStart, $tEnd, $tUnit, $sortBy, $sortOrder = 'ASC', $is_company = FALSE)
    {
        $params = self::validateParameters($metric, $tStart, $tEnd, $tUnit, $sortBy, $sortOrder);

        if (!empty($devEmailOrCompany)) {
            // We need to filter analytics by the developer or company name. If we do not
            // we will get back data for all apps with the app name, since they are not
            // unique.  For example, two developers can make an app named "test".
            if ($is_company) {
                // There isn't a "company" attribute to filter by, but the analytics
                // data stores the company data under "developer" dimension in the
                // format {orgName}@@@{CompanyName}.
                $org = $this->config->orgName;
                $params['filter'] = "(developer eq '{$org}@@@{$devEmailOrCompany}')";
            }
            else {
                // It is a developer, filter by email address.
                $params['filter'] = "(developer_email eq '{$devEmailOrCompany}')";
            }
        }

        $params['developer_app'] = $appName;

        $url = 'apps?';
        $first = true;
        foreach ($params as $name => $val) {
            if ($first) {
                $first = false;
            } else {
                $url .= '&';
            }
            $url .= $name . '=' . urlencode($val);
        }
        $this->get($url);
        $response = $this->responseObj['Response'];

        $datapoints = array();
        $timestamps = array();
        foreach ($response['TimeUnit'] as $timestamp) {
            $timestamps[] = floor($timestamp / 1000);
        }
        if (array_key_exists('stats', $response) && array_key_exists('data', $response['stats'])) {
            foreach ($response['stats']['data'] as $responseItem) {
                $itemCaption = '';
                foreach ($responseItem['identifier']['names'] as $key => $value) {
                    if ($value == 'developer_app') {
                        $itemCaption = $responseItem['identifier']['values'][$key];
                        break;
                    }
                }
                foreach ($responseItem['metric'] as $array) {
                    $env = $array['env'];
                    $i = 0;
                    foreach ($array['values'] as $metricValue) {
                        $datapoints[$itemCaption][$env][$timestamps[$i++]] = $metricValue;
                    }
                }
            }
        }
        return $datapoints;
    }
}
