<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\elementactions;

use Craft;
use craft\app\elements\db\ElementQueryInterface;
use craft\app\elements\User;
use craft\app\enums\AttributeType;
use craft\app\errors\Exception;
use craft\app\helpers\JsonHelper;

/**
 * Delete Users Element Action
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeleteUsers extends BaseElementAction
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc ComponentTypeInterface::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('app', 'Delete…');
	}

	/**
	 * @inheritDoc ElementActionInterface::isDestructive()
	 *
	 * @return bool
	 */
	public function isDestructive()
	{
		return true;
	}

	/**
	 * @inheritDoc ElementActionInterface::getTriggerHtml()
	 *
	 * @return string|null
	 */
	public function getTriggerHtml()
	{
		$undeletableIds = JsonHelper::encode($this->_getUndeletableUserIds());

		$js = <<<EOT
(function()
{
	var trigger = new Craft.ElementActionTrigger({
		handle: 'DeleteUsers',
		batch: true,
		validateSelection: function(\$selectedItems)
		{
			for (var i = 0; i < \$selectedItems.length; i++)
			{
				if ($.inArray(\$selectedItems.eq(i).find('.element').data('id').toString(), $undeletableIds) != -1)
				{
					return false;
				}
			}

			return true;
		},
		activate: function(\$selectedItems)
		{
			var modal = new Craft.DeleteUserModal(Craft.elementIndex.getSelectedElementIds(), {
				onSubmit: function()
				{
					Craft.elementIndex.submitAction('DeleteUsers', Garnish.getPostData(modal.\$container));
					modal.hide();

					return false;
				}
			});
		}
	});
})();
EOT;

		Craft::$app->templates->includeJs($js);
	}

	/**
	 * @inheritdoc
	 */
	public function performAction(ElementQueryInterface $query)
	{
		$users = $query->all();
		$undeletableIds = $this->_getUndeletableUserIds();

		// Are we transfering the user's content to a different user?
		$transferContentToId = $this->getParams()->transferContentTo;

		if (is_array($transferContentToId) && isset($transferContentToId[0]))
		{
			$transferContentToId = $transferContentToId[0];
		}

		if ($transferContentToId)
		{
			$transferContentTo = Craft::$app->users->getUserById($transferContentToId);

			if (!$transferContentTo)
			{
				throw new Exception(Craft::t('app', 'No user exists with the ID “{id}”.', ['id' => $transferContentTo]));
			}
		}
		else
		{
			$transferContentTo = null;
		}

		// Delete the users
		foreach ($users as $user)
		{
			if (!in_array($user->id, $undeletableIds))
			{
				Craft::$app->users->deleteUser($user, $transferContentTo);
			}
		}

		$this->setMessage(Craft::t('app', 'Users deleted.'));

		return true;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseElementAction::defineParams()
	 *
	 * @return array
	 */
	protected function defineParams()
	{
		return [
			'transferContentTo' => AttributeType::Mixed,
		];
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns a list of the user IDs that can't be deleted.
	 *
	 * @return array
	 */
	private function _getUndeletableUserIds()
	{
		if (!Craft::$app->getUser()->getIsAdmin())
		{
			// Only admins can delete other admins
			return User::find()->admin()->ids();
		}
		else
		{
			// Can't delete your own account from here
			return [Craft::$app->getUser()->getIdentity()->id];
		}
	}
}