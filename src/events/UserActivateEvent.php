<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\elements\User;

/**
 * User activate event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UserActivateEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var User|null The user model associated with the event.
     */
    public $user;
}
