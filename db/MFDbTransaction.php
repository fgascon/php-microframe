<?php
/**
 * MFDbTransaction class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright 2008-2013 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * MFDbTransaction represents a DB transaction.
 *
 * It is usually created by calling {@link MFDbConnection::beginTransaction}.
 *
 * The following code is a common scenario of using transactions:
 * <pre>
 * $transaction=$connection->beginTransaction();
 * try
 * {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... other SQL executions
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollback();
 * }
 * </pre>
 *
 * @property MFDbConnection $connection The DB connection for this transaction.
 * @property boolean $active Whether this transaction is active.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.db
 * @since 1.0
 */
class MFDbTransaction extends MFComponent
{
	private $_connection=null;
	private $_active;

	/**
	 * Constructor.
	 * @param MFDbConnection $connection the connection associated with this transaction
	 * @see MFDbConnection::beginTransaction
	 */
	public function __construct(MFDbConnection $connection)
	{
		$this->_connection=$connection;
		$this->_active=true;
	}

	/**
	 * Commits a transaction.
	 * @throws CException if the transaction or the DB connection is not active.
	 */
	public function commit()
	{
		if($this->_active && $this->_connection->getActive())
		{
			MF::trace('Committing transaction','system.db.MFDbTransaction');
			$this->_connection->getPdoInstance()->commit();
			$this->_active=false;
		}
		else
			throw new MFDbException(MF::t('core','MFDbTransaction is inactive and cannot perform commit or roll back operations.'));
	}

	/**
	 * Rolls back a transaction.
	 * @throws CException if the transaction or the DB connection is not active.
	 */
	public function rollback()
	{
		if($this->_active && $this->_connection->getActive())
		{
			MF::trace('Rolling back transaction','system.db.MFDbTransaction');
			$this->_connection->getPdoInstance()->rollBack();
			$this->_active=false;
		}
		else
			throw new MFDbException(MF::t('core','MFDbTransaction is inactive and cannot perform commit or roll back operations.'));
	}

	/**
	 * @return MFDbConnection the DB connection for this transaction
	 */
	public function getConnection()
	{
		return $this->_connection;
	}

	/**
	 * @return boolean whether this transaction is active
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean $value whether this transaction is active
	 */
	protected function setActive($value)
	{
		$this->_active=$value;
	}
}
