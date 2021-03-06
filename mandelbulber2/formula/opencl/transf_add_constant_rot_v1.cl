/**
 * Mandelbulber v2, a 3D fractal generator  _%}}i*<.        ____                _______
 * Copyright (C) 2020 Mandelbulber Team   _>]|=||i=i<,     / __ \___  ___ ___  / ___/ /
 *                                        \><||i|=>>%)    / /_/ / _ \/ -_) _ \/ /__/ /__
 * This file is part of Mandelbulber.     )<=i=]=|=i<>    \____/ .__/\__/_//_/\___/____/
 * The project is licensed under GPLv3,   -<>>=|><|||`        /_/
 * see also COPYING file in this folder.    ~+{i%+++
 *
 * Adds c constant to z vector
 * This formula contains aux.pos_neg

 * This file has been autogenerated by tools/populateUiInformation.php
 * from the file "fractal_transf_add_constant_rot_v1.cpp" in the folder formula/definition
 * D O    N O T    E D I T    T H I S    F I L E !
 */

REAL4 TransfAddConstantRotV1Iteration(REAL4 z, __constant sFractalCl *fractal, sExtendedAuxCl *aux)
{
	REAL4 rotadd = fractal->transformCommon.additionConstantA000;
	rotadd = Matrix33MulFloat4(fractal->transformCommon.rotationMatrix, rotadd);

	if (!fractal->transformCommon.functionEnabledFalse)
	{
		z += aux->pos_neg * rotadd;
	}
	else // iter controls
	{
		if (aux->i >= fractal->transformCommon.startIterationsA
				&& aux->i < fractal->transformCommon.stopIterationsA)
			z.x += aux->pos_neg * rotadd.x;
		if (aux->i >= fractal->transformCommon.startIterationsB
				&& aux->i < fractal->transformCommon.stopIterationsB)
			z.y += aux->pos_neg * rotadd.y;
		if (aux->i >= fractal->transformCommon.startIterationsC
				&& aux->i < fractal->transformCommon.stopIterationsC)
			z.z += aux->pos_neg * rotadd.z;
	}
	// update for next
	aux->pos_neg *= fractal->transformCommon.scaleNeg1;

	if (fractal->analyticDE.enabledFalse)
		aux->DE = aux->DE * fractal->analyticDE.scale1 + fractal->analyticDE.offset0;
	return z;
}