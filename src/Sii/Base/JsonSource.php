<?php
/**
 * @version 3/2/21 5:31 p. m.
 * @author  David Lopez <dleo.lopez@gmail.com>
 */

namespace HSDCL\DteCl\Sii\Base;

/**
 * Class JsonSource
 *
 * Clase que sirve para ser la fuente desde un JSON
 * @package HSDCL\DteCl\Sii\Base
 * @author  David Lopez <dleo.lopez@gmail.com>
 * @version 202207211450
 */
class JsonSource implements Source
{
    /**
     * JsonSource constructor.
     * @param string $cases
     */
    public function __construct(string $cases)
    {
        $decodeCases = json_decode($cases, true);;
        if (array_keys($decodeCases) !== range(0, count($decodeCases) - 1)) {
            $this->cases[] = $decodeCases;
            return;
        }
        $this->cases = $decodeCases;
    }

    /**
     * Desde el string crear los casos
     * @param array $folios
     * @return array
     * @author David Lopez <dleo.lopez@gmail.com>
     */
    public function getCases(array $folios = [], array $options = []): array
    {
        return $this->cases;
    }

    /**
     * @return mixed
     * @author David Lopez <dleo.lopez@gmail.com>
     */
    public function getInput()
    {
        return $this->cases;
    }
}
