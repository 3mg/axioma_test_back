<?php
/**
 * Created by PhpStorm.
 * User: nickolay
 * Date: 23.02.15
 * Time: 23:28
 */

namespace AppBundle\Faker\Provider;

use Application\Sonata\MediaBundle\Entity\Media;
use Buzz\Util\Url;
use Doctrine\ORM\EntityManager;
use JMS\DiExtraBundle\Annotation as DI;
use Knp\DoctrineBehaviors\Model\Translatable\Translatable;
use Nelmio\Alice\Instances\Processor\Methods\Faker;
use Sonata\MediaBundle\Entity\MediaManager;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Component\DependencyInjection\Dump\Container;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Intl\Intl;

/**
 * Class Employee
 * @package AppBundle\Faker\Provider\OtdamDrugim
 * 
 * @DI\Service("app.faker.a3mg", public=true)
 * @DI\Tag("bazinga_faker.provider")
 * @DI\Tag("hautelook_alice.faker.provider")
 */
class a3mgFaker extends \Faker\Provider\Base
{
    /**
     * @var EntityManager
     */
    protected $em;

    /** @var  MediaManager */
    protected $mediaManager;
    public function getMediaManager() { return $this->mediaManager; }

    /** @var  \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /**
     * @DI\InjectParams({
     *     "em" = @DI\Inject("doctrine.orm.entity_manager"),
     *     "mediaManager" = @DI\Inject("sonata.media.manager.media"),
     *     "container" = @DI\Inject("service_container"),
     *     "categoryManager" = @DI\Inject("sonata.classification.manager.category"),
     *     "contextManager" = @DI\Inject("sonata.classification.manager.context"),
     *     "faker" = @DI\Inject("hautelook_alice.bare_faker", required=false),
     * })
     */
    function __construct($em, $mediaManager, $container, $categoryManager, $contextManager, $faker)
    {
        $this->em = $em;
        $this->mediaManager = $mediaManager;
        $this->container = $container; // I need it to prevent cyclic dependency. It dev only service anyway.
        $this->categoryManager = $categoryManager;
        $this->contextManager = $contextManager;
        $this->faker = $faker;
    }
    
   /* public function unique($reset = false, $maxRetries = 10000)
    {
        $this->generator = $this->container->get("faker.generator");
        parent::unique($reset, $maxRetries);
    }*/

    public function getUser($usernameEmailOrId)
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;

        $qb = $em->getRepository("AppBundle:User")->createQueryBuilder("u");
        $qb->where("u.id = :id OR u.username = :id OR u.email = :id");
        $qb->setParameter("id", $usernameEmailOrId);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function getUserId($usernameEmailOrId)
    {
        $user = $this->getUser($usernameEmailOrId);

        return $user ? $user->getId() : null;
    }

    public function randomUser($nullable = false)
    {
        if ($nullable) {
            if (rand(0,1)>0.1) {
                return null;
            }
        }

        $em = $this->em;

        $query = $em->createQuery("SELECT COUNT(u) FROM AppBundle:User u");
        $count = $query->getSingleScalarResult();

        $query = $em->createQuery("SELECT u FROM AppBundle:User u");
        $query->setMaxResults(1)->setFirstResult(rand(0, $count-1));
        $user = $query->getOneOrNullResult();

        return $user;
    }

    public function encodePassword($user, $password)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');
        $encodedPassword = $passwordEncoder->encodePassword($user, $password);

        return $encodedPassword;
    }

    public function randomElementOfArray($array)
    {
        if (count($array) == 0) {
            return null;
        }

        return $array[rand(0, count($array) - 1)];
    }

    public function randomElementsOfArray($array, $probability = 0.3)
    {
        $result = [];
        for($i = 0; $i < count($array); $i++) {
            if (rand(0, 1) < $probability) {
                $result[] = $array[$i];
            }
        }
        if (count($result) == 0 && count($array) > 0) {
            $result = [$array[0]];
        }
        return $result;
    }

    public function callMethodChain($object, $chain, $parameters) {

        foreach($chain as $i => $m) {
            $object = call_user_func_array([$object, $m], $parameters[$i]);
            //echo \TVarDumper::dump($object, 5);
        }

        return $object;
    }

    /**
     * @param Translatable $self
     * @param $field
     * @param array $locales
     * @param $fakerMethod
     * @return mixed
     */
    public function translation(/*Translatable*/ $self, $field, array $locales, $fakerMethod, $values = '[]')
    {
        $values = json_decode($values, true);
        if (!is_array($values)) {
            $values = [];
        }

        //throw new \Exception("test");
        //$faker = $this->faker;
        $faker = $this->container->get("app.faker.processor_method");

        $args = array_slice(func_get_args(), 4);

        $defaultValue = null;

        foreach ($locales as $i => $locale) {
            //$value = call_user_func_array([$faker, $fakerMethod], $args);
            $localeCode = self::getFullLocaleCode($locale);

            if (array_key_exists($locale, $values)) {
                $value = $values[$locale];
            } else {
                $value = call_user_func_array([$faker, 'fake'], array_merge([$fakerMethod, $localeCode], $args));
            }

            if($self->getDefaultLocale() == $locale) {
                $defaultValue = $value;
            }

            $translation = $self->translate($locale);

            //\Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor()->setValue($translation, $field, $value);
            call_user_func([$translation, "set".ucfirst($field)], $value);

            //echo \TVarDumper::dump([$translation, $value], 3);
        }

        $self->mergeNewTranslations();

        echo ".";
        return $defaultValue;
    }


    public function localize($locale, $fakerMethod)
    {
        $faker = $this->container->get("app.faker.processor_method");

        $args = array_slice(func_get_args(), 2);

        $defaultValue = null;

        $localeCode = self::getFullLocaleCode($locale);

        $value = call_user_func_array([$faker, 'fake'], array_merge([$fakerMethod, $localeCode], $args));

        echo ".";
        return $value;
    }

    public function randomEntity($class)
    {
        $em = $this->em;

        $query = $em->createQuery("SELECT COUNT(c) FROM {$class} o");
        $count = $query->getSingleScalarResult();

        $query = $em->createQuery("SELECT c FROM {$class} o");
        $query->setMaxResults(1)->setFirstResult(rand(0, $count-1));
        $object = $query->getOneOrNullResult();

        return $object;
    }
    
    public function group($code)
    {
        $em = $this->em;

        $repo = $em->getRepository("Application\Sonata\UserBundle\Entity\Group");
        
        return $repo->findOneByCode($code);
    }
    
    public function groups($code)
    {
        $em = $this->em;

        $repo = $em->getRepository("Application\Sonata\UserBundle\Entity\Group");
        
        return $repo->findByCode($code);
    }
    
    public function eq($arg)
    {
        return $arg;
    }

    public function randomLocality()
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->em;

        $query = $em->createQuery("SELECT COUNT(l) FROM AppBundle:Locality l");
        $count = $query->getSingleScalarResult();

        $query = $em->createQuery("SELECT l FROM AppBundle:Locality l");
        $query->setMaxResults(1)->setFirstResult(rand(0, $count-1));
        $locality = $query->getOneOrNullResult();

        return $locality;
    }

    public function randomLocales()
    {
        $_locales = ["en", "fr", "de", "it", "es", "ru", "tr", "zh", "pt", "pl", "ja", "ar"];

        $locales = ["en"];
        foreach($_locales as $locale) {
            if(rand(0,5) == 0 && !in_array($locale, $locales)) {
                $locales[] = $locale;
            }
        }


        return $locales;
    }

    public function allLocales()
    {
        return ["en", "fr", "de", "it", "es", "ru", "tr", "zh", "pt", "pl", "ja", "ar"];
    }

    public function randomCountries()
    {
        $_countries = array_keys(Intl::getRegionBundle()->getCountryNames());

        $countries = ["RU"];
        foreach($_countries as $country) {
            if(rand(0, 5) == 0 && !in_array($country, $countries)) {
                $countries[] = $country;
            }
        }
        return $countries;
    }

    public function randomMedia($context, $width = 640, $height = 480, $category = null, $text = "Random_image", $color = true)
    {
        $allCategories = [
            null,
            "abstract",
            "animals",
            "business",
            "cats",
            "city",
            "food",
            "nightlife",
            "fashion",
            "people",
            "nature",
            "sports",
            "technics",
            "transport",
            "technics",
        ];
        if ("random" == $category || !in_array($category, $allCategories)) {
            $category = $allCategories[rand(0, count($allCategories) - 1)];
        }
        
        $url = "http://lorempixel.com/";
        if (!$color) {
            $url .= "g/";
        }
        
        if (null == $category) {
            $url .= "{$width}/{$height}/";
        } else {
            $url .= "{$width}/{$height}/{$category}/";
        }
        $url .= "?".rand(0, 10000);
        
        //$fixture = json_decode(file_get_contents(__DIR__."/../../DataFixtures/fixtures.json"));
        //$office_imgs = iterator_to_array(Finder::create()->name('*.jpg')->in(__DIR__.'/../../DataFixtures/images/Office photos')->depth(0));

        //$file = $office_imgs[array_keys($office_imgs)[rand(0, count($office_imgs)-1)]];

        //$file = parent::
        $file = $this->download($url);

        //var_dump([$url, $file, @getimagesize($file)]);
        if (false === @getimagesize($file)) return null;

        /** @var \Sonata\ClassificationBundle\Entity\CategoryManager $categoryManager */
        $categoryManager = $this->categoryManager;
        /** @var \Sonata\ClassificationBundle\Entity\ContextManager $contextManager */
        $contextManager = $this->contextManager;

        /** @var Media $media */
        $media = $this->getMediaManager()->create();
        $media->setBinaryContent($file);
        $media->setEnabled(true);
        $media->setName($text);
        $media->setDescription("Random photo");
        $media->setContext($context);
        $media->setProviderName('sonata.media.provider.image');

        $contextCode = $media->getContext() ?: ContextInterface::DEFAULT_CONTEXT;
        $category = $categoryManager->getRootCategory($contextCode);

        if (!$category && $contextCode) {
            $context = $contextManager->find($contextCode);
            if (!$context instanceof ContextInterface) {
                $context = $contextManager->create();

                $context->setId($contextCode);
                $context->setName($contextCode);
                $context->setEnabled(true);

                $this->contextManager->save($context, true);
            }

            $rootCategories = $categoryManager->getRootCategories(false);

            if (!array_key_exists($contextCode, $rootCategories)) {
                /** @var Category $category */
                $category = $categoryManager->create();
                $category->setName($contextCode);
                $category->setContext($context);
                $category->setEnabled(true);

                $categoryManager->save($category, true);
            } else {
                $category = $rootCategories[$contextCode];
            }
        }

        $media->setCategory($category);
        $this->mediaManager->save($media, true);

        return $media;
    }

    public function randomMedias($context, $min = 1, $max = 5, $width = 640, $height = 480, $category = null, $text = "Random image", $color = true)
    {
        $photos = [];
        $cnt = $this->numberBetween($min, $max);
        for ($i = 0; $i < $cnt; $i++) {
            $photo = $this->randomMedia($context, $width, $height, $category, $text, $color);
            if ($photo instanceof Media) {
                $photos[] = $photo;
            }
        }

        return $photos;
    }

    public function randomVideoMedia($context)
    {
        $file = $this->randomWebm();

        //var_dump([$url, $file, @getimagesize($file)]);
        if (!is_file($file)) return null;

        /** @var \Sonata\ClassificationBundle\Entity\CategoryManager $categoryManager */
        $categoryManager = $this->categoryManager;
        /** @var \Sonata\ClassificationBundle\Entity\ContextManager $contextManager */
        $contextManager = $this->contextManager;

        /** @var Media $media */
        $media = $this->getMediaManager()->create();
        $media->setBinaryContent($file);
        $media->setEnabled(true);
        $media->setName("Random video");
        $media->setDescription("Random video");
        $media->setContext($context);
        $media->setProviderName('sonata_media.providers.video_file');

        $contextCode = $media->getContext() ?: ContextInterface::DEFAULT_CONTEXT;
        $category = $categoryManager->getRootCategory($contextCode);

        if (!$category && $contextCode) {
            $context = $contextManager->find($contextCode);
            if (!$context instanceof ContextInterface) {
                $context = $contextManager->create();

                $context->setId($contextCode);
                $context->setName($contextCode);
                $context->setEnabled(true);

                $this->contextManager->save($context, true);
            }

            $rootCategories = $categoryManager->getRootCategories(false);

            if (!array_key_exists($contextCode, $rootCategories)) {
                /** @var Category $category */
                $category = $categoryManager->create();
                $category->setName($contextCode);
                $category->setContext($context);
                $category->setEnabled(true);

                $categoryManager->save($category, true);
            } else {
                $category = $rootCategories[$contextCode];
            }
        }

        $media->setCategory($category);
        $this->mediaManager->save($media, true);

        return $media;
    }

    public function randomVideoMedias($context, $max = 2, $probability = 0.1)
    {
        $photos = [];

        for ($i = 0; $i < $max; $i++) {
            $test = mt_rand(1, 10000);
            if ($test > $probability*10000) {
                continue;
            }
            
            $photo = $this->randomVideoMedia($context);
            $photos[] = $photo;
        }

        return $photos;
    }

    public function getWebmLandCache() {
        if (isset($this->webmLandVideoUrls) && is_array($this->webmLandVideoUrls) && count($this->webmLandVideoUrls) > 0) {
            return $this->webmLandVideoUrls;
        }

        $buzz = $this->container->get("buzz");
        $buzz->getClient()->setTimeout(20000);
        $buzz->getClient()->setIgnoreErrors(true);

        $videoUrls = [];

        $parsePage = function($url) use (&$buzz, &$videoUrls) {
            $response = $buzz->get($url);
            $html = $response->getContent();
            $crawler = new Crawler($html);

            /*
             * href:  http://webm.land/w/tXx3/
             * video: http://webm.land/media/tXx3.webm
             */
            $links = $crawler->filter("section.webms a");

            /** @var \DOMElement $domElement */
            foreach ($links as $domElement) {
                $href = $domElement->getAttribute("href");
                preg_match("/.*\\/w\\/([^\\/]+).*/", $href, $matches);
                $videoHash = count($matches) == 2 ? $matches[1] : null;

                if ($videoHash) {
                    $videoUrls[] = "http://webm.land/media/{$videoHash}.webm";
                }
            }
        };

        for ($i = 1; $i <= 10 ; $i++) {
            $webmLandUrl = new Url("http://webm.land/?page={$i}");
            $parsePage($webmLandUrl);
        }

        $this->webmLandVideoUrls = $videoUrls;

        return $videoUrls;
    }

    public function randomWebmUrl() {
        $videoUrls = $this->getWebmLandCache();

        if (count($videoUrls) == 0) {
            return null;
        }
        return $videoUrls[rand(0, count($videoUrls) - 1)];
    }

    public function randomWebm() {
        $url = $this->randomWebmUrl();

        if ($url) {
            return $this->download($url, "webm");
        }

        return null;
    }

    public function getDateString($time = "now")
    {
        $date = new \DateTime($time);
        return $date->format(\DateTime::ISO8601);
    }
    
    protected function download($url, $ext = "jpg")
    {
        $tmp_path = tempnam(sys_get_temp_dir(), "rand_img_").".{$ext}";
        if(file_exists($tmp_path)) unlink($tmp_path);

        $ch = curl_init($url);
        $fp = fopen($tmp_path, 'wb');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        //curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');

        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $tmp_path;
    }


    private static function getFullLocaleCode($locale)
    {
        $hash = [
            'ar' => 'ar_JO',
            'at' => 'at_AT',
            'be' => 'be_BE',
            'bg' => 'bg_BG',
            'bn' => 'bn_BD',
            'cs' => 'cs_CZ',
            'da' => 'da_DK',
            'de' => 'de_DE',
            'el' => 'el_GR',
            'en' => 'en_GB',
            'es' => 'es_ES',
            'fa' => 'fa_IR',
            'fi' => 'fi_FI',
            'fr' => 'fr_FR',
            'hu' => 'hu_HU',
            'hy' => 'hy_AM',
            'id' => 'id_ID',
            'is' => 'is_IS',
            'it' => 'it_IT',
            'ja' => 'ja_JP',
            'ka' => 'ka_GE',
            'kk' => 'kk_KZ',
            'ko' => 'ko_KR',
            'lv' => 'lv_LV',
            'me' => 'me_ME',
            'ne' => 'ne_NP',
            'nl' => 'nl_NL',
            'no' => 'no_NO',
            'pl' => 'pl_PL',
            'pt' => 'pt_PT',
            'ro' => 'ro_RO',
            'ru' => 'ru_RU',
            'sk' => 'sk_SK',
            'sl' => 'sl_SI',
            'sr' => 'sr_RS',
            'sv' => 'sv_SE',
            'tr' => 'tr_TR',
            'uk' => 'uk_UA',
            'vi' => 'vi_VN',
            'zh' => 'zh_CN',
        ];

        if (array_key_exists($locale, $hash)) {
            return $hash[$locale];
        } else {
            return $locale;
        }
    }
}
