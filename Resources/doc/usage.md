# Usage

## Archiving entities

Let's say you have an entity named ``Order``, which represents a customer's order on your website.
At some point you decide you want to archive some of those to a separate table, and perhaps even remove them from the existing table.
It's an easy way to make your orders unavailable to the rest of your application logic, and still have it available for viewing in your backend/CMS.

Taking that example, first you need to make sure the mentioned entities implement the correct interface:

```php
<?php

namespace Acme\YourBundle\Entity;

use CL\Bundle\ArchiverBundle\Archiver\Entity\ArchivableEntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="orders")
 */
class Order implements ArchivableEntityInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $title
     *
     * @ORM\Column(type="string")
     */
    protected $title;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Order
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
```

Now you need an entity that will represent the archived order:


```php
<?php

namespace Acme\YourBundle\Entity;

use CL\Bundle\ArchiverBundle\Archiver\Entity\ArchivedEntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="archived_orders")
 */
class ArchivedOrder implements ArchivedEntityInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     */
    protected $originalId;

    /**
     * @var array $data
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $data;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param int $originalId
     */
    public function setOriginalId($originalId)
    {
        $this->originalId = $originalId;
    }

    /**
     * @return int
     */
    public function getOriginalId()
    {
        return $this->originalId;
    }
}
```

**NOTE:** You are not required to use the above annotations, you could be using a separate xml/yaml configuration for this.

Having these entities set-up, all you need to do now is define the relation between the two in your service configuration:

```yaml
# Acme/YourBundle/Resources/config/services.yml
acme_your.archivable_order:
    class: Acme\YourBundle\Entity\Order
    tags:
        - { name: cl_archiver.archivable_entity, archived_entity: Acme\YourBundle\Entity\ArchivedOrder }
```

That's it, now use the archiver service to easily archive and unarchive your orders.
For instance, you could do this inside your controller:

```php
<?php
// src/Acme/YourBundle/Controller/ExampleController.php

namespace Acme\YourBundle\Controller;

use CL\Bundle\ArchiverBundle\Entity\ArchivableEntityExample;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ExampleController extends Controller
{
    public function archiveAction()
    {
        // ...
        $archiver      = $this->get('cl_archiver.archiver.entity');
        $order         = $em->find(...);
        $archivedOrder = $archiver->archive($order);
        // ...
    }

    public function unArchiveAction()
    {
        // ...
        $archiver      = $this->get('cl_archiver.archiver.entity');
        $archivedOrder = $em->find(...);
        $order         = $archiver->unarchive($archivedOrder);
        // ...
    }
}

```


## Archiving files

Coming soon!
