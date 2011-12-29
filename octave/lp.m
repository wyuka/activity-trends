function [retRed, retBlue, oldbp, elaspedtime] = lp(adjmat, B, m)

starttime = time;

p = rows(adjmat);
q = columns(adjmat);

labelsR = zeros(p,1);
labelsB = [1:q](:);


escflag = false;
oldbp = -1;
cntR = zeros(p,1);
cntB = zeros(q,1);

while (!escflag)
    escflag = true;
    
    Red = sparse(p,q);
    Blue = sparse(eye(q));

    for i = 1:p
        neighbour = find(adjmat(i,:));
        #[maximal, prevc, C] = mode(labelsB(neighbour));

        cntB(:) = 0;
        for k = 1:columns(neighbour)
            cntB(labelsB(neighbour(k)))++;
        end

        prevc = cntB(1);
        candidates = 1;
        for c = 2:q
            if (cntB(c) > prevc)
                prevc = cntB(c);
                candidates = c;
            elseif (cntB(c) == prevc)
                candidates = [candidates; c];
            end
        end
        #candidates = cell2mat(C);
        maximal = candidates(ceil(rand()*rows(candidates)));
        if (rows(find(labelsB(neighbour) == labelsR(i))) != prevc)
            labelsR(i) = maximal;
            escflag = false;
        end
        Red(i,maximal) = 1;
    end;

    bp = bipartitemodularity(B, Red, Blue, m);
    if (bp > oldbp)
        oldbp = bp;
        permRed = Red;
        permBlue = Blue;
    end

    for j = 1:q
        neighbour = find(adjmat(:,j));
        #[maximal, prevc, C] = mode(labelsR(neighbour));

        cntR(:) = 0;
        for k = 1:rows(neighbour)
            cntR(labelsR(neighbour(k)))++;
        end

        prevc = cntR(1);
        candidates = 1;
        for c = 2:p
            if (cntR(c) > prevc)
                prevc = cntR(c);
                candidates = c;
            elseif (cntR(c) == prevc)
                candidates = [candidates; c];
            end
        end
        #candidates = cell2mat(C);
        maximal = candidates(ceil(rand()*rows(candidates)));
        if (rows(find(labelsR(neighbour) == labelsB(j))) != prevc)
            labelsB(j) = maximal;
            escflag = false;
        end
        Blue(j,maximal) = 1;
    end;

    bp = bipartitemodularity(B, Red, Blue, m);
    if (bp > oldbp)
        oldbp = bp;
        permRed = Red;
        permBlue = Blue;
    end

end;

retRed = sparse(0,0);
retBlue = sparse(0,0);
for i = 1:q
    if(rows(find(permRed(:,i))) > 0 || rows(find(permBlue(:,i))) > 0)
        retRed = [retRed, permRed(:, i)];
        retBlue = [retBlue, permBlue(:, i)];
    end
end

endtime = time;
elaspedtime = endtime - starttime;