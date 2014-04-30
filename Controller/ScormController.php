<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\ScormBundle\Controller;

use Claroline\CoreBundle\Persistence\ObjectManager;
use Claroline\ScormBundle\Entity\ScormInfo;
use Claroline\ScormBundle\Event\Log\LogScormResultEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration as EXT;
use JMS\DiExtraBundle\Annotation as DI;

class ScormController extends Controller
{
    private $eventDispatcher;
    private $om;
    private $securityContext;
    private $scormInfoRepo;
    private $scormRepo;
    private $userRepo;

    /**
     * @DI\InjectParams({
     *     "eventDispatcher"    = @DI\Inject("event_dispatcher"),
     *     "om"                 = @DI\Inject("claroline.persistence.object_manager"),
     *     "securityContext"    = @DI\Inject("security.context")
     * })
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ObjectManager $om,
        SecurityContextInterface $securityContext
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->om = $om;
        $this->securityContext = $securityContext;
        $this->scormInfoRepo = $om->getRepository('ClarolineScormBundle:ScormInfo');
        $this->scormRepo = $om->getRepository('ClarolineScormBundle:Scorm');
        $this->userRepo = $om->getRepository('ClarolineCoreBundle:User');
    }

    /**
     * @EXT\Route(
     *     "/scorm/info/commit/{datasString}",
     *     name = "claro_scorm_info_commit",
     *     options={"expose"=true}
     * )
     *
     * @param string $datasString
     *
     * @return Response
     */
    public function commitScormInfo($datasString)
    {
        $datasArray = explode("<-;->", $datasString);
        $scormId = $datasArray[0];
        $studentId = $datasArray[1];
        $lessonMode = $datasArray[2];
        $lessonLocation = $datasArray[3];
        $lessonStatus = $datasArray[4];
        $credit = $datasArray[5];
        $scoreRaw = $datasArray[6];
        $scoreMin = $datasArray[7];
        $scoreMax = $datasArray[8];
        $sessionTime = $datasArray[9];
        $totalTime = $datasArray[10];
        $suspendData = $datasArray[11];
        $entry = $datasArray[12];
        $exitMode = $datasArray[13];

        if ($this->securityContext->getToken()->getUser()->getId() !== intval($studentId)) {
            throw new AccessDeniedException();
        }

        $sessionTimeInHundredth = $this->convertTimeInHundredth($sessionTime);
        $totalTimeInHundredth = $this->convertTimeInHundredth($totalTime);
        $totalTimeInHundredth += $sessionTimeInHundredth;

        $user = $this->userRepo->findOneById(intval($studentId));
        $scorm = $this->scormRepo->findOneById(intval($scormId));
        $scormInfo = $this->scormInfoRepo->findOneBy(
            array('user' => $user->getId(), 'scorm' => $scorm->getId())
        );

        if (is_null($scormInfo)) {
            $scormInfo = new ScormInfo();
            $scormInfo->setUser($user);
            $scormInfo->setScorm($scorm);
            $scormInfo->setLessonMode($lessonMode);
            $scormInfo->setCredit($credit);
        }

        $scormInfo->setLessonLocation($lessonLocation);
        $scormInfo->setLessonStatus($lessonStatus);
        $scormInfo->setScoreRaw(intval($scoreRaw));
        $scormInfo->setScoreMin(intval($scoreMin));
        $scormInfo->setScoreMax(intval($scoreMax));
        $scormInfo->setSessionTime($sessionTimeInHundredth);
        $scormInfo->setTotalTime($totalTimeInHundredth);
        $scormInfo->setEntry($entry);
        $scormInfo->setExitMode($exitMode);
        $scormInfo->setSuspendData($suspendData);

        $this->om->persist($scormInfo);
        $this->om->flush();

        $details = array();
        $details['scoreRaw'] = $scormInfo->getScoreRaw();
        $details['scoreMin'] = $scormInfo->getScoreMin();
        $details['scoreMax'] = $scormInfo->getScoreMax();
        $details['lessonStatus'] = $scormInfo->getLessonStatus();
        $details['sessionTime'] = $scormInfo->getSessionTime();
        $details['totalTime'] = $scormInfo->getTotalTime();
        $details['suspendData'] = $scormInfo->getSuspendData();
        $details['exitMode'] = $scormInfo->getExitMode();
        $details['credit'] = $scormInfo->getCredit();
        $details['lessonMode'] = $scormInfo->getLessonMode();

        $log = new LogScormResultEvent(
            "resource_scorm_result",
            $details,
            null,
            null,
            $scorm->getResourceNode(),
            null,
            $scorm->getResourceNode()->getWorkspace(),
            $user,
            null,
            null,
            null
        );
        $this->eventDispatcher->dispatch('log', $log);

        return new Response('', '204');
    }

    /**
     * Convert time (HHHH:MM:SS.hh) to integer (hundredth of second)
     *
     * @param string $time
     */
    private function convertTimeInHundredth($time) {
        $timeInArray = explode(':', $time);
        $timeInArraySec = explode('.', $timeInArray[2]);
        $timeInHundredth = 0;

        if (isset($timeInArraySec[1])) {

            if (strlen($timeInArraySec[1]) === 1) {
                $timeInArraySec[1] .= "0";
            }
            $timeInHundredth = intval($timeInArraySec[1]);
        }
        $timeInHundredth += intval($timeInArraySec[0]) * 100;
        $timeInHundredth += intval($timeInArray[1]) * 6000;
        $timeInHundredth += intval($timeInArray[0]) * 144000;

        return $timeInHundredth;
    }
}