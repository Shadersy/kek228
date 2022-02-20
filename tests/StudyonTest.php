<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use http\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Test\FixturesTrait;


class StudyonTest extends WebTestCase
{

    use FixturesTrait;


    public function makeClient()
    {
        $client = static::createClient();

        $client->disableReboot();

        $client->getContainer()->set(
            'App\Service\BillingClient',
            new BillingClientMock()
        );

        return $client;
    }

    private function doAuth(& $client, string $email, string $pass)
    {

        $crawler = $client->request('GET', '/login');

        $buttonCrawlerNode = $crawler->selectButton('submit');

        $form = $buttonCrawlerNode->form(array(
            'email' => $email,
            'password' => $pass,
        ));

        $client->submit($form);


        return $crawler;
    }

    private function doAuthClient( $client)
    {

        $crawler = $client->request('GET', '/login');

        $buttonCrawlerNode = $crawler->selectButton('submit');

        $form = $buttonCrawlerNode->form(array(
            'email' => 'admin@mail.ru',
            'password' => 'qweasd',
        ));

        $client->submit($form);


        return $client;
    }

    private function setFixtures()
    {
        $this->loadFixtures(array(
            'App\DataFixtures\CourseFixtures'
        ));
    }

    public function testRegisterSuccessfull(): void
    {
        $client = $this->makeClient();

        $crawler = $client->request('POST', '/signup');


        $buttonCrawlerNode = $crawler->selectButton('Принять');

        $form = $buttonCrawlerNode->form(array(
            'form[email]' => 'admin@mail.ru',
            'form[password]' => 'qwerty',
            'form[conformationPassword]' => 'qwerty'
        ));

       $client->submit($form);



        $this->assertTrue(
            $client->getResponse()->isRedirect('http://study-on.local:81/course'));

    }

  // тест на успешную авторизацию
    public function testAuthorization(): void
    {
        $client = $this->makeClient();
        $this->doAuth($client, 'shadersy98@mail.ru', 'qwerty');


        $this->assertTrue(
            $client->getResponse()->isRedirect('http://study-on.local:81/course'));
    }

//проверяем возможность анонима видеть список курсов и невозможность читать содержимое урока
    public function testCoursePage() : void
    {
        $client = $this->makeClient();
        $this->loadFixtures(array(
            'App\DataFixtures\CourseFixtures'
        ));


        $crawler = $client->request('GET', '/course');


        $crawler = $client->followRedirect();

        $this->assertCount(5, $crawler->filter('h2'));

        $link = $crawler
            ->filter('a:contains("Гитарный")')
            ->link();

        $crawler = $client->click($link);

        $this->assertStringContainsString(
            'Уроки',
            $client->getResponse()->getContent()
        );

        $link = $crawler
            ->filter('a:contains("Постановка рук")')
            ->link();

        $crawler = $client->click($link);

        $this->assertTrue(
            $client->getResponse()->isRedirect('/login'));


        $crawler = $client->request('GET', '/lesson/1');
        $this->assertTrue(
            $client->getResponse()->isRedirect('/login'));
    }



//    public function testDeletingOportunity() {
//        $client = $this->makeClient();
//        $this->loadFixtures(array(
//            'App\DataFixtures\CourseFixtures'
//        ));
//        $crawler = $this->doAuth($client, "admin@mail.ru", "qwerty");
//
//        $client->followRedirect();
//        $client->followRedirect();
//
//    }
//

    public function testNewCourse(): void
    {
        $client = $this->makeClient();
        $this->setFixtures();
        $crawler = $this->doAuth($client, "admin@mail.ru", "qwerty");
        $crawler = $client->followRedirect();
        $crawler = $client->followRedirect();

        $link = $crawler
            ->filter('a:contains("Новый")')
            ->link();
        $crawler = $client->click($link);


        $buttonCrawlerNode = $crawler->selectButton('Создать');


        $form = $buttonCrawlerNode->form();

        //все значения являются валидными
        $client->submit($form, array(
            'course[code]' => '15',
            'course[name]' => 'Guitar master',
            'course[description]' => 'some description'
        ));


//        //Проверяем, что редиректит  на главную после создания
        $this->assertTrue(
            $client->getResponse()->isRedirect('/course/'));

        $crawler = $client->followRedirect();

        //Количество курсов изменилось
        $this->assertCount(4, $crawler->filter('h2'));

        $linkCourse = $crawler->filter('a:contains("curse number6")')->link();
        $crawler = $client->click($linkCourse);

        //Проверяем, что можно перейти на страницу курса
        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );


    }

    //проверка статус-кода 404 при несуществующем курсе
    public function testInvalidUrlCourse() {

        $client = $this->makeClient();
        $this->setFixtures();
        $crawler = $this->doAuth($client, "admin@mail.ru", "qwerty");
        $crawler = $client->followRedirect();

        $crawler = $client->request('GET', 'http://study-on.local:81/course/32131');


        $this->assertEquals(
            404,
            $client->getResponse()->getStatusCode()
        );

    }

    public function testInvalidLesson() {
        $client = $this->makeClient();
        $this->setFixtures();
        $crawler = $this->doAuth($client, "admin@mail.ru", "qwerty");
        $crawler = $client->followRedirect();

        $crawler = $client->request('GET', 'http://study-on.local:81/lesson/150');

        $this->assertEquals(
            404,
            $client->getResponse()->getStatusCode()
        );
    }


    public function testFormCodeCourse() {
        $client = $this->makeClient();
        $this->setFixtures();
        $crawler = $this->doAuth($client, "admin@mail.ru", "qwerty");
        $crawler = $client->followRedirect();
        $crawler = $client->followRedirect();


        $link = $crawler
            ->filter('a:contains("Новый")')
            ->link();
        $crawler = $client->click($link);

        $buttonCrawlerNode = $crawler->selectButton('Создать');
        $form = $buttonCrawlerNode->form();

        //coursecode = barber-muzhskoy-parikmaher уже задействован в фикстуре
        $client->submit($form, array(
            'course[code]' => 'barber-muzhskoy-parikmaher',
            'course[name]' => 'Guitar master',
            'course[description]' => 'some description',
            'course[type]' => '0',
            'course[cost]' => '0'
        ));

        //проверяем на уникальность
        $this->assertStringContainsString(
            'This value is already used',
            $client->getResponse()->getContent()
        );


        $client->submit($form, array(
            'course[code]' => 'test',
            'course[name]' => 'test',
            'course[description]' => 'some description',
            'course[type]' => '0',
            'course[cost]' => '100'
        ));

        $this->assertStringContainsString(
            'Нельзя установить стоимость бесплатному курса  больше 0',
            $client->getResponse()->getContent()
        );


        $client->submit($form, array(
            'course[code]' => 'test',
            'course[name]' => 'test',
            'course[description]' => 'some description',
            'course[type]' => '1',
            'course[cost]' => '0'
        ));

        $this->assertStringContainsString(
            'У платных курсов должна быть указана цена',
            $client->getResponse()->getContent()
        );

        $client->submit($form, array(
            'course[code]' => 'test',
            'course[name]' => 'test',
            'course[description]' => 'some description',
            'course[type]' => '1',
            'course[cost]' => '-12'
        ));

        $this->assertStringContainsString(
            'Цена не может быть меньше 0',
            $client->getResponse()->getContent()
        );

    }


    public function testSimpleUser()
    {
        $client = $this->makeClient();
        $this->setFixtures();
        $crawler = $this->doAuth($client, "shadersy97@mail.ru", "qwerty");
        $crawler = $client->followRedirect();
        $crawler = $client->followRedirect();


        //не может редактировать по ссылке
        $crawler = $client->request('POST', '/course/1/edit');
        $this->assertTrue(
            $client->getResponse()->isRedirect('/course/'));
        $crawler = $client->followRedirect();

        //не может удалить по ссылке
        $crawler = $client->request('POST', '/course/1');
        $this->assertTrue(
            $client->getResponse()->isRedirect('/course/'));
        $crawler = $client->followRedirect();

        //нет кнопки создания курса
        $this->assertNotEquals(
            'Новый курс',
            $client->getResponse()->getContent()
        );

        //переходя по ссылке не создаст новый курс
        $crawler = $client->request('POST', '/course/new');
        $this->assertTrue(
            $client->getResponse()->isRedirect('/course/'));
        $crawler = $client->followRedirect();


        $crawler = $client->request('GET', '/course/1');
        $this->assertNotEquals(
            'Delete',
            $client->getResponse()->getContent()
        );
    }
}
