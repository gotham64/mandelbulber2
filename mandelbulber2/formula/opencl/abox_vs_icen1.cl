/**
 * Mandelbulber v2, a 3D fractal generator  _%}}i*<.        ____                _______
 * Copyright (C) 2020 Mandelbulber Team   _>]|=||i=i<,     / __ \___  ___ ___  / ___/ /
 *                                        \><||i|=>>%)    / /_/ / _ \/ -_) _ \/ /__/ /__
 * This file is part of Mandelbulber.     )<=i=]=|=i<>    \____/ .__/\__/_//_/\___/____/
 * The project is licensed under GPLv3,   -<>>=|><|||`        /_/
 * see also COPYING file in this folder.    ~+{i%+++
 *
 * ABoxVS_icen1, a formula from Mandelbulb3D.
 * Inspired from a 2D formula proposed by Kali at Fractal Forums
 * @reference
 * http://www.fractalforums.com/new-theories-and-research/kaliset-plus-boxfold-nice-new-2d-fractal/msg33670/#new
 * This formula contains aux.color and aux.actualScale

 * This file has been autogenerated by tools/populateUiInformation.php
 * from the file "fractal_abox_vs_icen1.cpp" in the folder formula/definition
 * D O    N O T    E D I T    T H I S    F I L E !
 */

REAL4 AboxVSIcen1Iteration(REAL4 z, __constant sFractalCl *fractal, sExtendedAuxCl *aux)
{
	aux->actualScale =
		fractal->mandelbox.scale + fractal->mandelboxVary4D.scaleVary * (fabs(aux->actualScale) - 1.0f);
	REAL4 c = aux->const_c;
	REAL4 oldZ = z;
	z.x = fabs(z.x + fractal->transformCommon.additionConstant111.x)
				- fabs(z.x - fractal->transformCommon.additionConstant111.x) - z.x;
	z.y = fabs(z.y + fractal->transformCommon.additionConstant111.y)
				- fabs(z.y - fractal->transformCommon.additionConstant111.y) - z.y;
	z.z = fabs(z.z + fractal->transformCommon.additionConstant111.z)
				- fabs(z.z - fractal->transformCommon.additionConstant111.z) - z.z;
	if (z.x != oldZ.x) aux->color += fractal->mandelbox.color.factor.x;
	if (z.y != oldZ.y) aux->color += fractal->mandelbox.color.factor.y;
	if (z.z != oldZ.z) aux->color += fractal->mandelbox.color.factor.z;

	if (fractal->transformCommon.juliaMode)
	{
		z += c * fractal->transformCommon.constantMultiplier111;
	}
	else
	{
		z += fractal->transformCommon.juliaC;
	}

	REAL rr = dot(z, z);
	// if (r2 < 1e-21f && r2 > -1e-21f)
	//	r2 = (r2 > 0) ? 1e-21f : -1e-21f;
	if (rr < fractal->mandelbox.mR2)
	{
		z *= fractal->mandelbox.mboxFactor1;
		aux->DE *= fractal->mandelbox.mboxFactor1;
		aux->color += fractal->mandelbox.color.factorSp1;
	}
	else if (rr < fractal->mandelbox.fR2)
	{
		REAL tglad_factor2 = fractal->mandelbox.fR2 / rr;
		z *= tglad_factor2;
		aux->DE *= tglad_factor2;
		aux->color += fractal->mandelbox.color.factorSp2;
	}

	z *= aux->actualScale; // aux->actualScale;
	aux->DE = aux->DE * fabs(aux->actualScale) + 1.0f;

	if (fractal->transformCommon.rotationEnabled && aux->i >= fractal->transformCommon.startIterations
			&& aux->i < fractal->transformCommon.stopIterations)
	{
		z = Matrix33MulFloat4(fractal->transformCommon.rotationMatrix, z);
	}

	if (fractal->transformCommon.juliaMode)
	{
		z += fractal->transformCommon.juliaC - c * fractal->transformCommon.constantMultiplier111;
	}
	else
	{
		z += c * fractal->transformCommon.constantMultiplier111 + fractal->transformCommon.juliaC;
	}
	return z;
}