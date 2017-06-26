//------------------ MAIN RENDER FUNCTION --------------------
kernel void SSAO(__global float *zBuffer, __global float *out, sParamsSSAO p)
{
	const unsigned int i = get_global_id(0);
	const int2 scr = (int2){i % p.width, i / p.width};
	const float2 scr_f = convert_float2(scr);

	float scaleFactor = (float)p.width / (p.quality * p.quality) / 2.0f;
	float aspectRatio = (float)p.width / p.height;

	float z = zBuffer[i];
	float totalAmbient = 0.0f;
	float quality = p.quality;

	// printf("width %d", p.width);

	if (z < 1.0e2f)
	{
		float2 scr2;
		scr2.x = ((float)scr.x / p.width - 0.5f) * aspectRatio;
		scr2.y = ((float)scr.y / p.height - 0.5f);
		scr2 *= z * p.fov;

		float ambient = 0.0f;
		for (float angle = 0.0f; angle < quality; angle += 1.0f)
		{
			float2 dir = (float2){cos(angle), sin(angle)};
			float maxDiff = -1e10;

			for (float r = 1.0f; r < quality; r += 1.0)
			{
				float rr = r * r * scaleFactor;
				float2 v = scr_f + rr * dir;

				if (((int)v.x == scr.x) && ((int)v.y == scr.y)) continue;
				if (v.x < 0 || v.x > p.width - 1 || v.y < 0 || v.y > p.height - 1) continue;

				float z2 = zBuffer[(int)v.x + (int)v.y * p.width];
				float2 v2;
				v2.x = (v.x / p.width - 0.5f) * aspectRatio;
				v2.y = (v.y / p.height - 0.5f);
				v2 *= z2 * p.fov;

				float dv = distance(scr2, v2);
				float dz = z2 - z;
				float diff = -dz / dv;

				maxDiff = max(maxDiff, diff);
			}
			float maxAngle = atan(maxDiff);
			ambient += -maxAngle / M_PI_F + 0.5f;
		}

		totalAmbient = ambient / quality;
		totalAmbient = max(totalAmbient, 0.0f);
	}
	out[i] = totalAmbient;
}