<?php
/**
 * @filesource Gcms/Query.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Gcms;

use Kotchasan\Date;
use Kotchasan\Language;

/**
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @return array
     */
    public function getAllLeave()
    {
        return \Kotchasan\Model::createQuery()
                            ->select('*')
                            ->from('leave')
                            ->cacheOn()
                            ->execute();
    }
}
