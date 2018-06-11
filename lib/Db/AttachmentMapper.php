<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Deck\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;
use PDO;


class AttachmentMapper extends DeckMapper implements IPermissionMapper {

	private $cardMapper;
	private $userManager;

	public function __construct(IDBConnection $db, CardMapper $cardMapper, IUserManager $userManager) {
		parent::__construct($db, 'deck_attachment', Attachment::class);
		$this->cardMapper = $cardMapper;
		$this->userManager = $userManager;
		$this->qb = $this->db->getQueryBuilder();
	}

	/**
	 * @param $id
	 * @return Entity|Attachment
	 * @throws \OCP\AppFramework\Db\DoesNotExistException
	 * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
	 */
	public function find($id) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('deck_attachment')
			->where($qb->expr()->eq('id', $id));

		$cursor = $qb->execute();
		$row = $cursor->fetch(PDO::FETCH_ASSOC);
		$cursor->closeCursor();
		return $this->mapRowToEntity($row);
	}

	/**
	 * Find all attachments for a card
	 *
	 * @param $cardId
	 * @return array
	 */
	public function findAll($cardId) {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('deck_attachment')
			->where($qb->expr()->eq('card_id', $cardId, IQueryBuilder::PARAM_INT));

		$entities = [];
		$cursor = $qb->execute();
		while($row = $cursor->fetch()){
			$entities[] = $this->mapRowToEntity($row);
		}
		$cursor->closeCursor();
		return $entities;
	}

	/**
	 * @param Attachment $attachment
	 * @throws \Exception
	 */
	public function mapParticipant(Attachment $attachment) {
		$userManager = $this->userManager;
		$attachment->resolveRelation('participant', function() use (&$userManager, &$attachment) {
			$user = $userManager->get($attachment->getParticipant());
			if ($user !== null) {
				return new User($user);
			}
			return null;
		});
	}


	/**
	 * Check if $userId is owner of Entity with $id
	 *
	 * @param $userId string userId
	 * @param $id int|string unique entity identifier
	 * @return boolean
	 */
	public function isOwner($userId, $id) {
		// TODO: Implement isOwner() method.
	}

	/**
	 * Query boardId for Entity of given $id
	 *
	 * @param $id int|string unique entity identifier
	 * @return int|null id of Board
	 */
	public function findBoardId($id) {
		try {
			$attachment = $this->find($id);
		} catch (\Exception $e) {
			return null;
		}
		$this->cardMapper->findBoardId($attachment->getCardId());
	}
}