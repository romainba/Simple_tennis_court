namespace Joomla\Module\Tennis\Site\Helper;

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

class TennisHelper
{
    public static function getData(Registry $params)
    {
        $limit = $params->get('limit', 10);
        $showChart = $params->get('show_chart', 1);

        return [
            'limit' => $limit,
            'chart' => $showChart
        ];
    }
}