<?php

namespace Kangaroorewards\Core\CustomerData;

class KangarooCustomer implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    private $session;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->session = $customerSession;
    }

    public function getSectionData()
    {
        $customer = $this->session->getCustomer();

        return (
        [
            'logged_in' => $this->session->isLoggedIn(),

            'customer' => [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'name' => $customer->getName(),
            ]
        ]
        );
    }
}

