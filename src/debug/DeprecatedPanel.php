<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\debug;

use Craft;
use yii\debug\Panel;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * Debugger panel that collects and displays deprecation error logs.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class DeprecatedPanel extends Panel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return 'Deprecated';
	}

	/**
	 * @inheritdoc
	 */
	public function getSummary()
	{
		return Craft::$app->getView()->render('@app/views/debug/deprecated/summary', ['panel' => $this]);
	}

	/**
	 * @inheritdoc
	 * @throws NotFoundHttpException if a `trace` parameter is in the query string, but its value isn’t a valid deprecation error log’s ID
	 */
	public function getDetail()
	{
		$request = Craft::$app->getRequest();

		if ($request->getQueryParam('clear'))
		{
			Craft::$app->deprecator->deleteAllLogs();
		}

		$logId = $request->getQueryParam('trace');

		if ($logId)
		{
			$log = Craft::$app->deprecator->getLogById($logId);

			if ($log === null)
			{
				throw new NotFoundHttpException('The requested deprecation error log could not be found.');
			}

			return Craft::$app->getView()->render('@app/views/debug/deprecated/traces', [
				'panel' => $this,
				'log' => $log
			]);
		}
		else
		{
			return Craft::$app->getView()->render('@app/views/debug/deprecated/detail', ['panel' => $this]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save()
	{
		return ArrayHelper::toArray(Craft::$app->deprecator->getRequestLogs());
	}
}
