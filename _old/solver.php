<?php
    require "hungarian.php";

    class SkillDemand
    {
        public $importance;
        public $bias;

        public function __construct($importance, $bias)
        {
            $this->importance = $importance;
            $this->bias = $bias;
        }
    }

    class Solver
    {
        public $displayOutput = false; // mostly unused at the moment
        public $numSkills;
        public $usedSkills;

        // multiplies floating point by this before converting to integer
        public $discretisation = 100.0;

        // tiny randomisation bypasses an endless loop, caused by excessive identical values, in our acquired Hungarian algorithm code
        // the more randomisation there is, the faster it goes. could be because the values are more ordered instead of the same, and that reduces the number of possibilities
        public $randomisation;

        // the cost of changing from the current situation. forces it to settle
        public $inertia;

        public $processing = false;
        public $iteration = 0;

        public $students;
        public $projects;

        public $tasks;
        public $taskStudents;

        public $projectMinima;

        public $studentProjects;
        public $projectStudents;

        public $dummies;

        public $cost;

        public static function memberScore($demand, $value)
        {
            $n = pow(2.0, $demand->bias);
            $result = pow($value / 4, $n);
            return $result;
        }

        public function iterate()
        {
            $this->processing = true;
            $this->iteration += 1;

            $displayOutput = $this->displayOutput;
            $numSkills = $this->numSkills;
            $usedSkills = $this->usedSkills;
            $discretisation = $this->discretisation;
            $randomisation = $this->randomisation;
            $inertia = $this->inertia;

            $students = $this->students;
            $projects = $this->projects;
            $tasks = $this->tasks;
            $projectMinima = $this->projectMinima;
            $projectTasks = $this->projectTasks;
            $taskStudents = $this->taskStudents;
            $studentProjects = $this->studentProjects;
            $projectStudents = $this->projectStudents;
            $dummies = $this->dummies;

            if (sizeof($tasks) != sizeof($students))
            {
                echo "Task and student arrays must be equal in length.";
                $this->iteration = -1;
                return false;
            }

            $displayOutput = $this->displayOutput;
            $randomisation = $this->randomisation;
            
            $totals = array_fill(0, sizeof($projects), array_fill(0, $numSkills, 0.0));
            $projectFills = [];
            foreach ($projects as $p => $project)
            {
                foreach ($usedSkills as $s)
                {
                    /*
                    $demand = $project[$s];
                    $total = 0.0;
                    foreach ($projectStudents[$p] as $y)
                    {
                        $memberScore = Solver::memberScore($demand, $students[$y][$s], $change);
                        $total += $change;
                    }
                    $totals[$p][$s] = $total;
                    */
                    $totals[$p][$s] = sizeof($projectStudents[$p]);
                }

                // collect how full are projects, with valid members
                $clevers = 0;
                foreach ($projectStudents[$p] as $y)
                {
                    if (!in_array($y, $dummies))
                        $clevers += 1;
                }
                $projectFills[$p] = $clevers;
            }
            
            $matrix = array();
            for ($y = 0; $y < sizeof($students); $y += 1)
            {
                set_time_limit(30);

                $currentProject = $studentProjects[$y];

                $row = array();
                for ($x = 0; $x < sizeof($tasks); $x += 1)
                {
                    $nextProject = $tasks[$x];
                    $outY = $taskStudents[$x];

                    $cost = 0.0;
                    //if ($nextProject != $currentProject)
                    {
                        /*
                        // measure how much the project suits the member's skills
                        // (i*a + j*b)/(i + j)
                        $memberScoreA = 0.0;
                        if ($currentProject >= 0)
                        {
                            // current project
                            $satisfaction = 0.0;
                            $impTotal = 0.0;
                            foreach ($usedSkills as $s)
                            {
                                $demand = $projects[$currentProject][$s];
                                $satisfaction += $demand->importance * $students[$y][$s];
                                $impTotal += $demand->importance;
                            }
                            if ($impTotal > 0.0)
                                $memberScoreA = $satisfaction / $impTotal;
                    }*//*
                        $memberScoreB = 0.0;
                        {
                            // changed project
                            $satisfaction = 0.0;
                            $impTotal = 0.0;
                            foreach ($usedSkills as $s)
                            {
                                $demand = $projects[$nextProject][$s];
                                $satisfaction += $demand->importance * $students[$y][$s];
                                $impTotal += $demand->importance;
                            }
                            //if ($impTotal > 0.0)
                                $memberScoreB = $satisfaction; // / $impTotal;
                        }
                        *//*{
                            // changed project
                            $satisfaction = 0.0;
                            $impTotal = 0.0;
                            foreach ($usedSkills as $s)
                            {
                                $demand = $projects[$currentProject][$s];
                                $satisfaction += $demand->importance * $students[$y][$s];
                                $impTotal += $demand->importance;
                            }
                            if ($impTotal > 0.0)
                                $memberScoreB -= $satisfaction / $impTotal;
                        }
                        */

                        //$projectScoreA = 0.0;
                        $projectScoreB = 0.0;
                        foreach ($usedSkills as $s)
                        {
                            // measure how significant the member is to satisfying the project's needs
                            // i*a1^n/(a1^n + a2^n)
                            /*
                            if ($currentProject >= 0)
                            {
                                // current project
                                $demand = $projects[$currentProject][$s];
                                $satisfaction = Solver::memberScore($demand, $students[$y][$s]);
                                $total = $totals[$currentProject][$s];
                                if ($total > 0.0)
                                    $satisfaction /= $total;
                                $projectScoreA += $demand->importance * $satisfaction;
                            }
                            *//*
                            {
                                // changed project
                                $demand = $projects[$nextProject][$s];
                                $outSatisfaction = Solver::memberScore($demand, $students[$outY][$s]);
                                $satisfaction = Solver::memberScore($demand, $students[$y][$s]);
                                $total = $totals[$nextProject][$s];
                                $nextTotal = $total + $satisfaction - $outSatisfaction;
                                if ($nextTotal > 0.0)
                                    $projectScoreB += $demand->importance * $satisfaction / $nextTotal;
                            }
                            */
                            {
                                $total = $totals[$nextProject][$s];
                                foreach ($usedSkills as $s)
                                {
                                    $demand = $projects[$nextProject][$s];
                                    $sum = 0.0;
                                    foreach ($projectStudents[$nextProject] as $py)
                                    {
                                        $memberScore = Solver::memberScore($demand, $students[$py][$s]);
                                        $sum += $memberScore;
                                    }
                                    if ($total > 0.0)
                                    {
                                        $outMemberScore = Solver::memberScore($demand, $students[$outY][$s]);
                                        $inMemberScore = Solver::memberScore($demand, $students[$y][$s]);

                                        //$scoreA = $sum / $total;
                                        $scoreB = ($sum + $inMemberScore - $outMemberScore) / $total;
                                        $projectScoreB += $demand->importance * $scoreB; //($scoreB - $scoreA);
                                    }
                                }
                            }
                            /*
                            {
                                $demand = $projects[$nextProject][$s];
                                $memberScore = Solver::memberScore($demand, $students[$y][$s]);
                                $memberScoreB = Solver::memberScore($demand, $students[$outY][$s]);
                                $total = $totals[$nextProject][$s]; // + $change - $changeB;
                                if ($total > 0.0)
                                    $projectScoreB += $demand->importance * pow($memberScore / $total, pow(0.5, $demand->bias));
                            }
                            *//*
                            if (in_array($y, $dummies))
                            {
                                $difference = $projectFills[$currentProject] - $projectMinima[$currentProject];
                                $difference -= 1;
                                if ($difference < 0) // dummy member leaving a minimal project
                                    $d -= $lastResort;

                                $difference = $projectFills[$nextProject] - $projectMinima[$nextProject];
                                $difference -= 1;
                                if ($difference < 0) // dummy member joining a minimal project
                                    $d += $lastResort;
                            }
                            else
                            {
                                $difference = $projectFills[$currentProject] - $projectMinima[$currentProject];
                                $difference -= 1;
                                if ($difference < 0) // valid member leaving a minimal project
                                    $d += $lastResort;

                                $difference = $projectFills[$nextProject] - $projectMinima[$nextProject];
                                $difference -= 1;
                                if ($difference < 0) // valid member joining a minimal project
                                    $d -= $lastResort;
                            }
                            */
                        }

                        //$cost += $memberScoreA * $projectScoreA - $memberScoreB * $projectScoreB;
                        //$cost = -$projectScoreB * sizeof($projectStudents[$nextProject]) * $memberScoreB;
                        $cost = -$projectScoreB; // * sizeof($projectStudents[$nextProject]) * $memberScoreB;
                    }
                    $element = $discretisation * $cost;
                    if ($element > PHP_INT_MAX)
                    {
                        $this->iteration = -1;
                        return false;
                    }
                    $discrete = (int)$element;
                    $discrete += random_int(0, $randomisation);
                    if ($nextProject != $currentProject)
                        $discrete += $inertia;
                    $row[$x] = $discrete;
                }
                $matrix[$y] = $row;
            }

            $h = new RPFK\Hungarian\Hungarian($matrix);

            set_time_limit(30);
            $assignments = $h->solve($displayOutput, sizeof($tasks) * sizeof($tasks));

            $this->cost = $h->cost($assignments);

            if ($assignments == null)
            {
                $this->iteration = -1;
                return false;
            }

            $projectStudents = array_fill(0, sizeof($projects), []);
            $studentProjects = [];
            foreach ($assignments as $y => $x)
            {
                $p = $tasks[$x];
                array_push($projectStudents[$p], $y);
                $studentProjects[$y] = $p;
            }

            $this->projectStudents = $projectStudents;
            $this->studentProjects = $studentProjects;

            $this->processing = false;
            return true;
        }
    }
?>
