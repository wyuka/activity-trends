function Q, Q1 = bipartitemodularity(B, Red, Blue, m)
Q = sum(full((Red .* (B*Blue))(:)))/m;