load adjlist.dat

m = rows(adjlist);
adjmat = sparse(adjlist(:,1), adjlist(:,2), ones(rows(adjlist),1));

nullmodel = tonullmodel(adjmat, m);
B = adjmat - nullmodel;

[red, blue, lpbp, lptim] = lp(adjmat, B, m);
[red, blue, brimbp, brimtim] = brim(adjmat, B, red, blue, m);
disp("Bipartite Modularity:"), disp(brimbp)
disp("Total elapsed time:"), disp(lptim + brimtim)