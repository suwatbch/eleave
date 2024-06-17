<?php
/**
 * @filesource modules/eleave/controllers/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Export;

use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Template;

/**
 * export.php?module=eleave-export&typ=csv|print
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * export
     *
     * @param Request $request
     */
    public function export(Request $request)
    {
        $typ = $request->get('typ')->toString();
        if ($typ === 'csv') {
            // CSV
            return \Eleave\Csv\View::execute($request);
        }
        return false;
    }
}
