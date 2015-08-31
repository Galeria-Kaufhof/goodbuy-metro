<?php

namespace AppBundle\Service;

use AppBundle\Entity\Customer;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class CouponMapperService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Map N unused coupon codes to the given customer
     *
     * We need very strict isolation in order to avoid mapping
     * an already mapped code to another user in case of lots
     * of calls to mapNToCustomer happening in parallel.
     *
     * Therefore, we wrap everything into a transaction
     * and read with SELECT ... FOR UPDATE.
     *
     * This ensures a) that no codes are mapped at all if not all
     * mappings where successful, and b) locks the row of a free
     * code that is currently read.
     *
     * @see https://dev.mysql.com/doc/refman/5.5/en/innodb-locking-reads.html
     *
     * @param $count
     * @param \AppBundle\Entity\Customer $customer
     * @return bool
     */
    public function mapNToCustomer($count, Customer $customer)
    {
        $this->em->getConnection()->setTransactionIsolation(Connection::TRANSACTION_SERIALIZABLE);
        $this->em->getConnection()->beginTransaction();
        try {
            for ($i = 0; $i < $count; $i++) {
                $rsm = new ResultSetMappingBuilder($this->em);
                $rsm->addRootEntityFromClassMetadata('\AppBundle\Entity\Couponcode', 'c');

                $query = $this->em->createNativeQuery(
                    'SELECT * FROM couponcode WHERE customer_id IS NULL LIMIT 1 FOR UPDATE',
                    $rsm
                );
                $couponcode = $query->getOneOrNullResult();

                if (is_null($couponcode)) {
                    $this->em->getConnection()->rollback();
                    return false;
                }

                $couponcode->setCustomer($customer);

                //
                if (strlen($couponcode->getCode()) === 64) {
                    $normalizedEmployeeNumber = preg_replace("/[^a-zA-Z0-9-]+/", "", $customer->getEmployeeNumber());
                    $code = $couponcode->getCode();
                    $newCode = substr($code, 0, 45);
                    $newCode .= str_pad(substr($normalizedEmployeeNumber, 0, 17), 17, '_');
                    $newCode .= $customer->getSalesdivision();
                    $newCode .= substr($code, -1, 1);
                    $couponcode->setCode($newCode);
                }

                $this->em->persist($couponcode);
                $this->em->flush();
            }
            $this->em->getConnection()->commit();
            return true;
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            return false;
        }
    }
}
