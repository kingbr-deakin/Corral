<?php
/*
    The code in this document is based on work by Robert Koster.
    https://github.com/rpfk/Hungarian
    The original work was licensed under the MIT License and must contain the following notice:

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.
*/

namespace RPFK\Hungarian;

class Hungarian
{
    /*
     * The assignment cost matrix to be minimised.
     */
    public $matrix = [];

    /*
     * The reduced cost matrix.
     */
    protected $reduced = [];

    /*
     * The primed zeros of the matrix.
     */
    protected $primed = [];

    /*
     * The starred zeros of the matrix.
     */
    protected $starred = [];

    /*
     * The covered lines of the matrix.
     */
    protected $covered = [
        'column' => [],
        'row' => []
    ];
    
    // solver output text
    protected $outputText = [];
    
    public function __construct(array $matrix)
    {
        $this->isValid($matrix);
        $this->matrix = $matrix;
        $this->reduced = $matrix;
    }

    public function isValid(array $matrix)
    {
        if (count($matrix) == false) {
            throw new \Exception('Number of rows in matrix returns false.');
        }
        foreach ($matrix as $key => $row) {
            if (count($row) !== count(array_intersect_key($row, ...$matrix))) {
                throw new \Exception(printf('Column keys of row %u do not correspond to the column keys found in the rest of the matrix.', $key));
            }
        }
        return true;
    }

    /*
     * Reduces the cost matrix
     */
    protected function reduce()
    {
        /*
         * Reduces all rows of the matrix
         */
        foreach ($this->reduced as $row => $cells) {
            $min = min($cells);
            foreach ($cells as $column => $cell) {
                $this->reduced[$row][$column] -= $min;
            }
        }

        $transposed = array_map(null, ...$this->reduced);

        /*
         * Reduces all columns of the matrix
         */
        foreach ($transposed as $column => $cells) {
            $min = min($cells);
            foreach ($cells as $row => $cell) {
                $this->reduced[$row][$column] -= $min;
            }
        }
    }

    public function addPrime($row, $column)
    {
        $this->primed[$row] = $column;
        return $this;
    }

    public function addStar($row, $column)
    {
        $this->starred[$row] = $column;
        return $this;
    }

    public function getPrimed()
    {
        return $this->primed;
    }

    public function hasPrimeInColumn($column)
    {
        return (bool)array_search($column, $this->primed, true);
    }

    public function getPrimeFromColumn($column)
    {
        return array_search($column, $this->primed, true);
    }

    public function hasPrimeInRow($row)
    {
        return array_key_exists($row, $this->primed);
    }

    public function getPrimeFromRow($row)
    {
        if (!key_exists($row, $this->primed)) {
            return false;
        }
        return $this->primed[$row];
    }

    public function hasStarInColumn($column)
    {
        return array_search($column, $this->starred, true) !== false;
    }

    public function getStarFromColumn($column)
    {
        return array_search($column, $this->starred, true);
    }

    public function hasStarInRow($row)
    {
        return array_key_exists($row, $this->starred);
    }

    public function getStarFromRow($row)
    {
        if (!key_exists($row, $this->starred)) {
            return false;
        }
        return $this->starred[$row];
    }

    public function getZeroMatrix()
    {
        $zeros = [];
        foreach ($this->reduced as $row => $cells) {
            $zeros[$row] = array_keys($cells, 0, true);
        }
        return $zeros;
    }

    public function getCoveredZeroMatrix($zero_matrix)
    {
        $covered_zero_matrix = [];
        foreach ($zero_matrix as $row => $cells) {
            foreach ($cells as $column) {
                if (in_array($row, $this->covered['row'], true) || in_array($column, $this->covered['column'], true)) {
                    $covered_zero_matrix[$row][] = $column;
                }
            }
        }
        return $covered_zero_matrix;
    }

    public function getNonCoveredZeroMatrix($zero_matrix)
    {
        $non_covered_zero_matrix = [];
        foreach ($zero_matrix as $row => $cells) {
            foreach ($cells as $column) {
                if (!in_array($row, $this->covered['row'], true) && !in_array($column, $this->covered['column'], true)) {
                    $non_covered_zero_matrix[$row][] = $column;
                }
            }
        }
        return $non_covered_zero_matrix;
    }
    
    protected function addOutput($text)
    {
        array_push($this->outputText, sprintf(...func_get_args()));
    }
    
    public function outputMatrix($matrix)
    {
        /*
        foreach ($matrix[key($matrix)] as $column => $cell) {
            if (array_search($column, $this->covered['column'], true) !== false) {
                printf("C       ");
            } else {
                printf("        ");
            }
        }
        */
        $this->addOutput("<table align='center' style='border-collapse: collapse;'>");
        $this->addOutput("<tr><th style='padding: 4px; width: 48px;'></th>");
        for ($i = 0; $i < sizeof($matrix); $i += 1)
            $this->addOutput("<th style='padding: 4px; width: 48px;'>C-" . $i . "</th>");
        $this->addOutput("</tr>");
        foreach ($matrix as $row => $cells) {
            $this->addOutput("<tr><th style='padding: 4px; height: 16px;'>R-" . $row . "</th>");
            foreach ($cells as $column => $cell) {
                $this->addOutput("<td style='padding: 4px; border: 1px solid #e0e0e0;'>");
                if (isset($this->starred[$row]) && $this->starred[$row] === $column)
                    $this->addOutput("<span style='color: red; font-weight: bold'>");
                else
                    $this->addOutput("<span>");
                $this->addOutput("%d", $cell);
                if (isset($this->primed[$row]) && $this->primed[$row] === $column) {
                    $this->addOutput("'");
                }
                $this->addOutput("</span></td>");
            }
            //if (array_search($row, $this->covered['row'], true) !== false) {
            //    $this->addOutput("C");
            //}
            $this->addOutput("</tr>");
        }
        $this->addOutput("</table><br>");
    }
    
    public function solve($print = false, $timeOut = 0)
    {
        $print ? $this->outputMatrix($this->matrix) : null;
        /*
         * Preliminary Steps:
         *  -  Generate reduced matrix
         *  -  For each row
         *     - Star first non-covered zero
         *     - Cover column of starred zero
         */
        $this->reduce();
        $print ? $this->outputMatrix($this->reduced, 'Reduced cost matrix:') : null;
        foreach ($this->reduced as $row => $cells) {
            $columns = array_diff(array_keys($cells, 0, true), $this->covered['column']);
            if (isset($columns[0])) {
                $this->addStar($row, $columns[0]);
                $this->covered['column'][] = $columns[0];
            }
        }
        $print ? $this->outputMatrix($this->reduced) : null;

        /*
         * Generate zero matrix
         */
        
        start:
        $zero_matrix = $this->getZeroMatrix();
        $non_covered_zero_matrix = $this->getNonCoveredZeroMatrix($zero_matrix);
        while ($non_covered_zero_matrix) {
            $timeOut -= 1;
            if ($timeOut == 0)
                return null;
            /*
             * Step 1:
             *  -  Select first non-covered zero and prime this selected zero
             *  -  If has starred zero in row of selected zero
             *     - Uncover column of starred zero
             *     - Cover row of starred zero
             *     Else
             *     - Step 2
             */
            $row = key($non_covered_zero_matrix);
            $column = $non_covered_zero_matrix[$row][0];
            $this->addPrime($row, $column);
            if ($this->hasStarInRow($row)) {

                // get column from the starred zero in the row
                $column = $this->getStarFromRow($row);

                // uncover the column of the starred zero
                $key = array_search($column, $this->covered['column'], true);
                unset($this->covered['column'][$key]);

                // cover the row
                $this->covered['row'][] = $row;
            } else {

                /*
                 * Step 2:
                 *  -  Get the sequence of starred and primed zeros connecting to the initial primed zero
                 *     - Get the starred zero in the column of the primed zero
                 *     - Get the primed zero in the row of the starred zero
                 *  -  Unstar the starred zeros from the sequence
                 *  -  Star the primed zeros from the sequence
                 *  -  Empty the list with primed zeros
                 *  -  Empty the list with covered columns and covered rows
                 *  -  Cover the columns with a starred zero in it
                 */
                $starred = [];
                $primed = [];
                $primed[$row] = $column;
                $i = $row;
                while (true) {
                    if (!$this->hasStarInColumn($primed[$i])) {

                        // Unstar the starred zeros from the sequence
                        foreach ($starred as $row => $column) {
                            unset($this->starred[$row]);
                        }

                        // Star the primed zeros from the sequence
                        foreach ($primed as $row => $column) {
                            $this->addStar($row, $column);
                        }

                        // Empty the list with primed zeros
                        $this->primed = [];

                        // Empty the list with covered columns
                        $this->covered['column'] = [];

                        // Empty the list with covered columns
                        $this->covered['row'] = [];

                        // Cover the columns with a starred zero in it
                        foreach ($this->starred as $row => $column) {
                            $this->covered['column'][] = $column;
                        }
                        break 1;
                    }

                    $star_row = $this->getStarFromColumn($primed[$i]);
                    $star_column = $primed[$i];
                    $starred[$star_row] = $star_column;

                    if ($this->hasPrimeInRow($star_row)) {
                        $prime_row = $star_row;
                        $prime_column = $this->getPrimeFromRow($prime_row);
                        $primed[$prime_row] = $prime_column;
                    } else {
                        return null;
                    }

                    $i = $prime_row;
                }
            }

            $print ? $this->outputMatrix($this->reduced) : null;

            $zero_matrix = $this->getZeroMatrix();
            $non_covered_zero_matrix = $this->getNonCoveredZeroMatrix($zero_matrix);
        }

        $timeOut -= 1;
        if ($timeOut == 0)
            return null;
        
        /*
         * Step 3:
         *  -  If the number of covered columns is equal to the number of rows/columns of the cost matrix
         *     - The currently starred zeros show the optimal solution
         *
         */
        if (count($this->covered['column']) + count($this->covered['row']) === count($this->reduced)) {
            return $this->starred;
        } else {
            $non_covered_reduced_matrix = [];
            $once_covered_reduced_matrix = [];
            $twice_covered_reduced_matrix = [];
            foreach ($this->reduced as $row => $cells) {
                foreach ($cells as $column => $cell) {
                    if (!in_array($row, $this->covered['row'], true) && !in_array($column, $this->covered['column'], true)) {
                        $non_covered_reduced_matrix[$row][$column] = $cell;
                    } elseif (in_array($row, $this->covered['row'], true) && in_array($column, $this->covered['column'], true)) {
                        $twice_covered_reduced_matrix[$row][$column] = $cell;
                    } else {
                        $once_covered_reduced_matrix[$row][$column] = $cell;
                    }
                }
            }

            $min = INF;
            foreach ($non_covered_reduced_matrix as $row => $cells) {
                foreach ($cells as $column => $cell) {
                    $min = ($cell < $min) ? $cell : $min;
                }
            }
            foreach ($non_covered_reduced_matrix as $row => $cells) {
                foreach ($cells as $column => $cell) {
                    $this->reduced[$row][$column] -= $min;
                }
            }
            foreach ($twice_covered_reduced_matrix as $row => $cells) {
                foreach ($cells as $column => $cell) {
                    $this->reduced[$row][$column] += $min;
                }
            }

            goto start;
        }
    }
    
    public function getOutput()
    {
        return $this->outputText;
    }
    
    public function clearOutput()
    {
        $this->outputText = [];
    }
    
    public function takeOutput()
    {
        $output = $this->getOutput();
        $this->clearOutput();
        return $output;
    }
    
    public function rowAssignments()
    {
        return $this->starred;
    }
    
    public function cost($rowAssignments)
    {
        $cost = 0;
        foreach ($rowAssignments as $row => $column)
            $cost += $this->matrix[$row][$column];
        return $cost;
    }
}
